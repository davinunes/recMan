# -*- coding: utf-8 -*-
"""
QR Code generator library (Python) - Project Nayuki
https://www.nayuki.io/page/qr-code-generator-library
Copyright (c) Project Nayuki. (MIT License)
Standalone zero-dependency QR Code generator in pure Python 3.
"""

from __future__ import annotations
import itertools
from typing import List, Sequence, Tuple, Union


class QrCode:
    """Represents an immutable square grid of black and white cells (modules)."""

    MIN_VERSION = 1
    MAX_VERSION = 40

    class Ecc:
        """Error correction level in a QR Code symbol."""
        def __init__(self, ordinal: int, format_bits: int):
            self.ordinal = ordinal
            self.format_bits = format_bits

    Ecc.LOW      = Ecc(0, 1)
    Ecc.MEDIUM   = Ecc(1, 0)
    Ecc.QUARTILE = Ecc(2, 3)
    Ecc.HIGH     = Ecc(3, 2)

    @staticmethod
    def encode_text(text: str, ecl: "QrCode.Ecc") -> "QrCode":
        """Returns a QR Code representing the given Unicode text string at the given error correction level."""
        segs = QrSegment.make_segments(text)
        return QrCode.encode_segments(segs, ecl)

    @staticmethod
    def encode_binary(data: Union[bytes, Sequence[int]], ecl: "QrCode.Ecc") -> "QrCode":
        """Returns a QR Code representing the given binary data at the given error correction level."""
        return QrCode.encode_segments([QrSegment.make_bytes(data)], ecl)

    @staticmethod
    def encode_segments(segs: Sequence["QrSegment"], ecl: "QrCode.Ecc",
                        minversion: int = 1, maxversion: int = 40,
                        mask: int = -1, boostecl: bool = True) -> "QrCode":
        if not (QrCode.MIN_VERSION <= minversion <= maxversion <= QrCode.MAX_VERSION) or not (-1 <= mask <= 7):
            raise ValueError("Invalid value")

        for version in range(minversion, maxversion + 1):
            data_capacity_bits = QrCode._get_num_data_codewords(version, ecl) * 8
            data_used_bits = QrSegment.get_total_bits(segs, version)
            if data_used_bits is not None and data_used_bits <= data_capacity_bits:
                break
        else:
            raise ValueError("Data too long for QR Code")

        if boostecl:
            for newecl in (QrCode.Ecc.MEDIUM, QrCode.Ecc.QUARTILE, QrCode.Ecc.HIGH):
                if data_used_bits <= QrCode._get_num_data_codewords(version, newecl) * 8:
                    ecl = newecl

        bb = []
        for seg in segs:
            bb.extend(int(b) for b in format(seg.mode.mode_bits, "04b"))
            bb.extend(int(b) for b in format(seg.num_chars, f"0{seg.mode.num_char_count_bits(version)}b"))
            bb.extend(seg.bit_data)

        data_capacity_bits = QrCode._get_num_data_codewords(version, ecl) * 8
        assert len(bb) <= data_capacity_bits
        bb.extend([0] * min(4, data_capacity_bits - len(bb)))
        bb.extend([0] * ((8 - len(bb) % 8) % 8))
        assert len(bb) % 8 == 0

        pad_byte = 0xEC
        while len(bb) < data_capacity_bits:
            bb.extend(int(b) for b in format(pad_byte, "08b"))
            pad_byte ^= 0xEC ^ 0x11

        data_codewords = []
        for i in range(0, len(bb), 8):
            data_codewords.append(int("".join(str(b) for b in bb[i : i + 8]), 2))

        return QrCode(version, ecl, data_codewords, mask)

    def __init__(self, version: int, ecl: "QrCode.Ecc", data_codewords: Sequence[int], mask: int):
        if not (QrCode.MIN_VERSION <= version <= QrCode.MAX_VERSION):
            raise ValueError("Version value out of range")
        if not (-1 <= mask <= 7):
            raise ValueError("Mask value out of range")

        self.version = version
        self.size = version * 4 + 17
        self.error_correction_level = ecl

        self._modules = [[False] * self.size for _ in range(self.size)]
        self._is_function = [[False] * self.size for _ in range(self.size)]

        self._draw_function_patterns()
        all_codewords = self._add_ecc_and_interleave(data_codewords)
        self._draw_codewords(all_codewords)

        if mask == -1:
            min_penalty = 1 << 30
            best_mask = 0
            for m in range(8):
                self._apply_mask(m)
                self._draw_format_bits(m)
                penalty = self._get_penalty_score()
                if penalty < min_penalty:
                    min_penalty = penalty
                    best_mask = m
                self._apply_mask(m)
            mask = best_mask

        self.mask = mask
        self._apply_mask(mask)
        self._draw_format_bits(mask)

    def get_module(self, x: int, y: int) -> bool:
        """Returns the color of the module at (x, y), False for light and True for dark."""
        return (0 <= x < self.size and 0 <= y < self.size) and self._modules[y][x]

    def to_svg_str(self, border: int = 4, light_color: str = "#ffffff", dark_color: str = "#000000") -> str:
        """Returns a string representing the QR Code as an SVG XML document."""
        if border < 0:
            raise ValueError("Border must be non-negative")
        parts = []
        full_size = self.size + border * 2
        parts.append(f'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 {full_size} {full_size}" stroke="none">\n')
        parts.append(f'\t<rect width="{full_size}" height="{full_size}" fill="{light_color}"/>\n')
        parts.append(f'\t<path d="')
        path_items = []
        for y in range(self.size):
            for x in range(self.size):
                if self.get_module(x, y):
                    path_items.append(f"M{x + border},{y + border}h1v1h-1z")
        parts.append(" ".join(path_items))
        parts.append(f'" fill="{dark_color}"/>\n')
        parts.append('</svg>\n')
        return "".join(parts)

    def _draw_function_patterns(self):
        for i in range(self.size):
            self._set_function_module(6, i, i % 2 == 0)
            self._set_function_module(i, 6, i % 2 == 0)

        self._draw_finder_pattern(3, 3)
        self._draw_finder_pattern(self.size - 4, 3)
        self._draw_finder_pattern(3, self.size - 4)

        align_pat_pos = self._get_alignment_pattern_positions()
        num_align = len(align_pat_pos)
        for i in range(num_align):
            for j in range(num_align):
                if not ((i == 0 and j == 0) or (i == 0 and j == num_align - 1) or (i == num_align - 1 and j == 0)):
                    self._draw_alignment_pattern(align_pat_pos[i], align_pat_pos[j])

        self._draw_format_bits(0)
        self._draw_version()

    def _draw_format_bits(self, mask: int):
        data = self.error_correction_level.format_bits << 3 | mask
        rem = data
        for _ in range(10):
            rem = (rem << 1) ^ ((rem >> 9) * 0x537)
        bits = (data << 10 | rem) ^ 0x5412

        for i in range(0, 6):
            self._set_function_module(8, i, ((bits >> i) & 1) != 0)
        self._set_function_module(8, 7, ((bits >> 6) & 1) != 0)
        self._set_function_module(8, 8, ((bits >> 7) & 1) != 0)
        self._set_function_module(7, 8, ((bits >> 8) & 1) != 0)
        for i in range(9, 15):
            self._set_function_module(14 - i, 8, ((bits >> i) & 1) != 0)

        for i in range(0, 8):
            self._set_function_module(self.size - 1 - i, 8, ((bits >> i) & 1) != 0)
        for i in range(8, 15):
            self._set_function_module(8, self.size - 15 + i, ((bits >> i) & 1) != 0)
        self._set_function_module(8, self.size - 8, True)

    def _draw_version(self):
        if self.version < 7:
            return
        rem = self.version
        for _ in range(12):
            rem = (rem << 1) ^ ((rem >> 11) * 0x1F25)
        bits = self.version << 12 | rem

        for i in range(18):
            bit = ((bits >> i) & 1) != 0
            a = self.size - 11 + i % 3
            b = i // 3
            self._set_function_module(a, b, bit)
            self._set_function_module(b, a, bit)

    def _draw_finder_pattern(self, x: int, y: int):
        for dy in range(-4, 5):
            for dx in range(-4, 5):
                dist = max(abs(dx), abs(dy))
                xx, yy = x + dx, y + dy
                if 0 <= xx < self.size and 0 <= yy < self.size:
                    self._set_function_module(xx, yy, dist != 2 and dist != 4)

    def _draw_alignment_pattern(self, x: int, y: int):
        for dy in range(-2, 3):
            for dx in range(-2, 3):
                self._set_function_module(x + dx, y + dy, max(abs(dx), abs(dy)) != 1)

    def _set_function_module(self, x: int, y: int, is_dark: bool):
        self._modules[y][x] = is_dark
        self._is_function[y][x] = True

    def _add_ecc_and_interleave(self, data: Sequence[int]) -> List[int]:
        version = self.version
        ecl = self.error_correction_level
        num_blocks = QrCode._NUM_ERROR_CORRECTION_BLOCKS[ecl.ordinal][version]
        block_ecc_len = QrCode._ECC_CODEWORDS_PER_BLOCK[ecl.ordinal][version]
        raw_codewords = QrCode._get_num_raw_data_modules(version) // 8
        num_short_blocks = num_blocks - raw_codewords % num_blocks
        short_block_len = raw_codewords // num_blocks

        blocks: List[List[int]] = []
        rs_div = QrCode._reed_solomon_compute_divisor(block_ecc_len)
        k = 0
        for i in range(num_blocks):
            dat = list(data[k : k + short_block_len - block_ecc_len + (1 if i >= num_short_blocks else 0)])
            k += len(dat)
            ecc = QrCode._reed_solomon_compute_remainder(dat, rs_div)
            if i >= num_short_blocks:
                dat.append(0)
            blocks.append(dat + ecc)

        result: List[int] = []
        for i in range(len(blocks[0])):
            for j in range(num_blocks):
                if i != short_block_len - block_ecc_len or j >= num_short_blocks:
                    result.append(blocks[j][i])
        return result

    def _draw_codewords(self, data: Sequence[int]):
        i = 0
        for right in range(self.size - 1, 0, -2):
            if right <= 6:
                right -= 1
            for vert in range(self.size):
                for j in range(2):
                    x = right - j
                    upwards = ((right + 1) & 2) == 0
                    y = (self.size - 1 - vert) if upwards else vert
                    if not self._is_function[y][x] and i < len(data) * 8:
                        self._modules[y][x] = ((data[i >> 3] >> (7 - (i & 7))) & 1) != 0
                        i += 1

    def _apply_mask(self, mask: int):
        for y in range(self.size):
            for x in range(self.size):
                if self._is_function[y][x]:
                    continue
                invert = False
                if mask == 0:   invert = (x + y) % 2 == 0
                elif mask == 1: invert = y % 2 == 0
                elif mask == 2: invert = x % 3 == 0
                elif mask == 3: invert = (x + y) % 3 == 0
                elif mask == 4: invert = (x // 3 + y // 2) % 2 == 0
                elif mask == 5: invert = x * y % 2 + x * y % 3 == 0
                elif mask == 6: invert = (x * y % 2 + x * y % 3) % 2 == 0
                elif mask == 7: invert = ((x + y) % 2 + x * y % 3) % 2 == 0
                self._modules[y][x] = self._modules[y][x] ^ invert

    def _get_penalty_score(self) -> int:
        result = 0
        size = self.size
        for y in range(size):
            run_color = False
            run_val = 0
            for x in range(size):
                if self._modules[y][x] == run_color:
                    run_val += 1
                    if run_val == 5:
                        result += 3
                    elif run_val > 5:
                        result += 1
                else:
                    run_color = self._modules[y][x]
                    run_val = 1
        for x in range(size):
            run_color = False
            run_val = 0
            for y in range(size):
                if self._modules[y][x] == run_color:
                    run_val += 1
                    if run_val == 5:
                        result += 3
                    elif run_val > 5:
                        result += 1
                else:
                    run_color = self._modules[y][x]
                    run_val = 1
        for y in range(size - 1):
            for x in range(size - 1):
                c = self._modules[y][x]
                if c == self._modules[y][x + 1] == self._modules[y + 1][x] == self._modules[y + 1][x + 1]:
                    result += 3
        black_count = sum(sum(1 for c in row if c) for row in self._modules)
        total = size * size
        k = (abs(black_count * 20 - total * 10) + total - 1) // total - 1
        result += k * 10
        return result

    def _get_alignment_pattern_positions(self) -> List[int]:
        if self.version == 1:
            return []
        num = self.version // 7 + 2
        step = (self.version * 8 + num * 3 + 5) // (num * 4 - 4) * 2
        result = [6]
        pos = self.size - 7
        for _ in range(num - 1):
            result.insert(1, pos)
            pos -= step
        return result

    @staticmethod
    def _get_num_raw_data_modules(ver: int) -> int:
        size = ver * 4 + 17
        result = size * size - 64 * 3 - (size - 16) * 2 + 9
        if ver >= 2:
            num_align = ver // 7 + 2
            result -= (num_align * num_align - 3) * 25
            result += (num_align - 2) * 2 * 10
        if ver >= 7:
            result -= 6 * 3 * 2
        return result

    @staticmethod
    def _get_num_data_codewords(ver: int, ecl: "QrCode.Ecc") -> int:
        return QrCode._get_num_raw_data_modules(ver) // 8 - \
               QrCode._ECC_CODEWORDS_PER_BLOCK[ecl.ordinal][ver] * \
               QrCode._NUM_ERROR_CORRECTION_BLOCKS[ecl.ordinal][ver]

    @staticmethod
    def _reed_solomon_compute_divisor(degree: int) -> List[int]:
        result = [0] * (degree - 1) + [1]
        root = 1
        for _ in range(degree):
            for j in range(len(result)):
                result[j] = QrCode._reed_solomon_multiply(result[j], root)
                if j + 1 < len(result):
                    result[j] ^= result[j + 1]
            root = QrCode._reed_solomon_multiply(root, 0x02)
        return result

    @staticmethod
    def _reed_solomon_compute_remainder(data: Sequence[int], divisor: Sequence[int]) -> List[int]:
        result = [0] * len(divisor)
        for b in data:
            factor = b ^ result.pop(0)
            result.append(0)
            for i, coef in enumerate(divisor):
                result[i] ^= QrCode._reed_solomon_multiply(coef, factor)
        return result

    @staticmethod
    def _reed_solomon_multiply(x: int, y: int) -> int:
        z = 0
        for i in reversed(range(8)):
            z = (z << 1) ^ ((z >> 7) * 0x11D)
            z ^= ((y >> i) & 1) * x
        return z

    _ECC_CODEWORDS_PER_BLOCK: List[List[int]] = [
        [-1,  7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28, 28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28],
        [-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30, 28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
        [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
    ]

    _NUM_ERROR_CORRECTION_BLOCKS: List[List[int]] = [
        [-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4, 4, 4, 4, 4, 6, 6, 6, 6, 7, 8, 8,  9,  9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25],
        [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49],
        [-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8, 8, 10, 12, 16, 12, 17, 16, 18, 21, 20, 23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68],
        [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81],
    ]


class QrSegment:
    """A segment of character/binary/numeric data in a QR Code symbol."""

    class Mode:
        def __init__(self, mode_bits: int, num_bits_char_count: Tuple[int, int, int]):
            self.mode_bits = mode_bits
            self._num_bits_char_count = num_bits_char_count

        def num_char_count_bits(self, ver: int) -> int:
            return self._num_bits_char_count[(ver + 7) // 17]

    Mode.NUMERIC      = Mode(0x1, (10, 12, 14))
    Mode.ALPHANUMERIC = Mode(0x2, (9, 11, 13))
    Mode.BYTE         = Mode(0x4, (8, 16, 16))
    Mode.KANJI        = Mode(0x8, (8, 10, 12))
    Mode.ECI          = Mode(0x7, (0, 0, 0))

    @staticmethod
    def make_bytes(data: Union[bytes, Sequence[int]]) -> "QrSegment":
        bb = []
        for b in data:
            bb.extend(int(c) for c in format(b, "08b"))
        return QrSegment(QrSegment.Mode.BYTE, len(data), bb)

    @staticmethod
    def make_segments(text: str) -> List["QrSegment"]:
        if text == "":
            return []
        return [QrSegment.make_bytes(text.encode("utf-8"))]

    @staticmethod
    def get_total_bits(segs: Sequence["QrSegment"], version: int) -> Union[int, None]:
        result = 0
        for seg in segs:
            ccbits = seg.mode.num_char_count_bits(version)
            if seg.num_chars >= (1 << ccbits):
                return None
            result += 4 + ccbits + len(seg.bit_data)
        return result

    def __init__(self, mode: "QrSegment.Mode", num_chars: int, bit_data: Sequence[int]):
        self.mode = mode
        self.num_chars = num_chars
        self.bit_data = list(bit_data)
