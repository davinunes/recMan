#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Microserviço em Python para Geração de PDFs com Typst na Porta 5050
recMan - Sistema de Gestão de Recursos e Regimento Interno
"""

import os
import sys
import json
import time
import base64
import subprocess
import tempfile
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import parse_qs, urlparse

PORT = 5050

# Descobre os diretórios do projeto
PY_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.abspath(os.path.join(PY_DIR, '..'))
TEMPLATES_DIR = os.path.join(ROOT_DIR, 'typst_templates')


def find_typst_binary():
    """Tenta localizar o binário do typst no sistema, pasta local do projeto ou PATH."""
    candidates = [
        os.path.join(ROOT_DIR, 'bin', 'typst'),
        os.path.join(PY_DIR, 'typst'),
        '/usr/local/bin/typst',
        '/usr/bin/typst',
        '/root/.cargo/bin/typst',
        os.path.expanduser('~/.cargo/bin/typst'),
        '/snap/bin/typst',
        'typst'
    ]

    for path in candidates:
        try:
            res = subprocess.run([path, '--version'], capture_output=True, text=True)
            if res.returncode == 0:
                return path
        except Exception:
            continue

    return None


def find_banner_image():
    """Localiza a imagem do banner LayoutMiami.jpg no servidor remoto ou ambiente local."""
    candidates = [
        '/var/www/reportPDFpython/LayoutMiami.jpg',
        os.path.join(ROOT_DIR, 'addons', 'api-pdf', 'LayoutMiami.jpg'),
        os.path.join(ROOT_DIR, 'reportPDFpython', 'LayoutMiami.jpg'),
        os.path.join(TEMPLATES_DIR, 'LayoutMiami.jpg'),
        os.path.join(PY_DIR, 'LayoutMiami.jpg')
    ]
    for c in candidates:
        if os.path.exists(c):
            return c.replace('\\', '/')
    return '/var/www/reportPDFpython/LayoutMiami.jpg'


TYPST_BIN = find_typst_binary()


def run_typst(template_name, input_data=None):
    """Compila um template Typst e retorna os bytes do PDF gerado e tempo em ms."""
    global TYPST_BIN
    
    if not TYPST_BIN:
        TYPST_BIN = find_typst_binary()

    if not TYPST_BIN:
        err_msg = (
            "Binário do 'typst' não encontrado no servidor!\n"
            "Para instalar rapidamente no Linux:\n"
            "curl -L https://github.com/typst/typst/releases/latest/download/typst-x86_64-unknown-linux-musl.tar.xz | tar -xJ\n"
            "cp typst-x86_64-unknown-linux-musl/typst /usr/local/bin/"
        )
        raise FileNotFoundError(err_msg)

    start_time = time.time()
    template_path = os.path.join(TEMPLATES_DIR, template_name)
    
    if not os.path.exists(template_path):
        raise FileNotFoundError(f"Template Typst não encontrado: {template_path}")

    # Se for regimento e não tiver dados enviados, carrega o regimento/database.json por padrão
    if template_name == 'regimento.typ' and (not input_data or not isinstance(input_data, dict) or len(input_data) == 0):
        reg_json_path = os.path.join(ROOT_DIR, 'regimento', 'database.json')
        if os.path.exists(reg_json_path):
            with open(reg_json_path, 'r', encoding='utf-8') as f:
                input_data = json.load(f)

    if input_data is None:
        input_data = {}

    # Define automaticamente o caminho correto da imagem LayoutMiami.jpg
    if 'banner_path' not in input_data or not input_data['banner_path']:
        input_data['banner_path'] = find_banner_image()

    func_name = 'regimento-doc' if template_name == 'regimento.typ' else 'parecer-doc'

    # Cria arquivos temporários dentro do ROOT_DIR para garantir caminhos relativos perfeitos no Typst
    json_tmp_file = None
    entry_tmp_file = None
    pdf_tmp_file = None

    try:
        json_tmp_file = tempfile.NamedTemporaryFile(dir=ROOT_DIR, prefix='tmp_data_', suffix='.json', mode='w', encoding='utf-8', delete=False)
        json.dump(input_data, json_tmp_file, ensure_ascii=False)
        json_tmp_file.close()

        rel_json_path = os.path.relpath(json_tmp_file.name, ROOT_DIR).replace('\\', '/')
        rel_template_path = os.path.relpath(template_path, ROOT_DIR).replace('\\', '/')

        entry_tmp_file = tempfile.NamedTemporaryFile(dir=ROOT_DIR, prefix='tmp_entry_', suffix='.typ', mode='w', encoding='utf-8', delete=False)
        entry_tmp_file.write(f'#import "{rel_template_path}": {func_name}\n')
        entry_tmp_file.write(f'#{func_name}(json("{rel_json_path}"))\n')
        entry_tmp_file.close()

        pdf_tmp_file = tempfile.NamedTemporaryFile(dir=ROOT_DIR, prefix='tmp_out_', suffix='.pdf', delete=False)
        pdf_tmp_file.close()

        cmd = [TYPST_BIN, 'compile', os.path.basename(entry_tmp_file.name), os.path.basename(pdf_tmp_file.name)]
        proc = subprocess.run(cmd, capture_output=True, text=True, cwd=ROOT_DIR)
        elapsed_ms = round((time.time() - start_time) * 1000, 2)

        if proc.returncode != 0:
            stderr_msg = proc.stderr or proc.stdout or "Erro desconhecido na compilação do Typst"
            raise RuntimeError(f"Erro no Typst (código {proc.returncode}): {stderr_msg.strip()}")

        with open(pdf_tmp_file.name, 'rb') as f:
            pdf_bytes = f.read()

        return pdf_bytes, elapsed_ms

    finally:
        for f_tmp in [json_tmp_file, entry_tmp_file, pdf_tmp_file]:
            if f_tmp and os.path.exists(f_tmp.name):
                try:
                    os.remove(f_tmp.name)
                except Exception:
                    pass


class TypstHandler(BaseHTTPRequestHandler):

    def _send_json(self, status_code, data):
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data, ensure_ascii=False).encode('utf-8'))

    def _send_pdf(self, pdf_bytes, filename="documento.pdf"):
        self.send_response(200)
        self.send_header('Content-Type', 'application/pdf')
        self.send_header('Content-Disposition', f'inline; filename="{filename}"')
        self.send_header('Content-Length', str(len(pdf_bytes)))
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(pdf_bytes)

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        self.end_headers()

    def do_GET(self):
        parsed_path = urlparse(self.path)
        if parsed_path.path in ['/health', '/status']:
            bin_status = TYPST_BIN if TYPST_BIN else "NÃO INSTALADO / NÃO ENCONTRADO"
            self._send_json(200, {
                'status': 'ok' if TYPST_BIN else 'warning_no_typst_binary',
                'service': 'typst-pdf-api',
                'port': PORT,
                'typst_binary': bin_status,
                'banner_image': find_banner_image(),
                'root_dir': ROOT_DIR,
                'templates': os.listdir(TEMPLATES_DIR) if os.path.exists(TEMPLATES_DIR) else []
            })
        else:
            self._send_json(404, {'status': 'error', 'message': 'Endpoint não encontrado'})

    def do_POST(self):
        parsed_path = urlparse(self.path)
        query_params = parse_qs(parsed_path.query)
        want_base64 = query_params.get('base64', ['false'])[0].lower() in ['true', '1']

        content_length = int(self.headers.get('Content-Length', 0))
        post_data = {}
        if content_length > 0:
            raw_body = self.rfile.read(content_length).decode('utf-8')
            if raw_body.strip():
                try:
                    post_data = json.loads(raw_body)
                except Exception as e:
                    self._send_json(400, {'status': 'error', 'message': f'JSON inválido: {str(e)}'})
                    return

        try:
            if parsed_path.path == '/gerar_pdf':
                pdf_bytes, elapsed_ms = run_typst('parecer.typ', post_data)
                filename = f"parecer_{str(post_data.get('notificacao', 'teste')).replace('/', '_')}.pdf"

            elif parsed_path.path == '/gerar_regimento':
                pdf_bytes, elapsed_ms = run_typst('regimento.typ', post_data)
                filename = "regimento_interno.pdf"
            else:
                self._send_json(404, {'status': 'error', 'message': 'Endpoint desconhecido'})
                return

            if want_base64:
                pdf_b64 = base64.b64encode(pdf_bytes).decode('utf-8')
                self._send_json(200, {
                    'status': 'success',
                    'elapsed_ms': elapsed_ms,
                    'pdf_size_bytes': len(pdf_bytes),
                    'pdf_base64': pdf_b64,
                    'engine': 'Typst CLI (Porta 5050)'
                })
            else:
                self._send_pdf(pdf_bytes, filename=filename)

        except Exception as err:
            err_msg = str(err)
            sys.stderr.write(f"[TYPST ERROR] {err_msg}\n")
            sys.stderr.flush()
            self._send_json(500, {
                'status': 'error',
                'message': err_msg,
                'engine': 'Typst CLI (Porta 5050)'
            })


def main():
    print(f"Servidor Typst API iniciado na porta {PORT}...")
    print(f"Binário Typst: {TYPST_BIN or 'NÃO ENCONTRADO'}")
    print(f"Banner de Topo: {find_banner_image()}")
    print(f"Raiz do Projeto: {ROOT_DIR}")
    print(f"Templates em: {TEMPLATES_DIR}")
    server = HTTPServer(('0.0.0.0', PORT), TypstHandler)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nEncerrando servidor Typst API...")
        server.server_close()


if __name__ == '__main__':
    main()
