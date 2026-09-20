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
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TEMPLATES_DIR = os.path.join(BASE_DIR, 'typst_templates')


def find_typst_binary():
    """Tenta localizar o binário do typst no sistema ou PATH."""
    for path in ['typst', 'typst.exe', '/usr/local/bin/typst', '/usr/bin/typst']:
        try:
            res = subprocess.run([path, '--version'], capture_output=True, text=True)
            if res.returncode == 0:
                return path
        except Exception:
            continue
    return 'typst'


TYPST_BIN = find_typst_binary()


def run_typst(template_name, input_data=None):
    """Compila um template Typst e retorna os bytes do PDF gerado e tempo em ms."""
    start_time = time.time()
    template_path = os.path.join(TEMPLATES_DIR, template_name)
    
    if not os.path.exists(template_path):
        raise FileNotFoundError(f"Template Typst não encontrado: {template_path}")

    with tempfile.NamedTemporaryFile(suffix='.pdf', delete=False) as tmp_pdf:
        output_pdf_path = tmp_pdf.name

    try:
        cmd = [TYPST_BIN, 'compile']
        
        # Se houver dados JSON de entrada, passa via --input data="..."
        if input_data:
            json_str = json.dumps(input_data, ensure_ascii=False)
            cmd.extend(['--input', f'data={json_str}'])
            
        cmd.extend([template_path, output_pdf_path])

        proc = subprocess.run(cmd, capture_output=True, text=True, cwd=BASE_DIR)
        elapsed_ms = round((time.time() - start_time) * 1000, 2)

        if proc.returncode != 0:
            stderr_msg = proc.stderr or proc.stdout or "Erro desconhecido na compilação do Typst"
            raise RuntimeError(f"Erro na compilação Typst: {stderr_msg}")

        with open(output_pdf_path, 'rb') as f:
            pdf_bytes = f.read()

        return pdf_bytes, elapsed_ms

    finally:
        if os.path.exists(output_pdf_path):
            try:
                os.remove(output_pdf_path)
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
            self._send_json(200, {
                'status': 'ok',
                'service': 'typst-pdf-api',
                'port': PORT,
                'typst_binary': TYPST_BIN,
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
                # Compila parecer
                pdf_bytes, elapsed_ms = run_typst('parecer.typ', post_data)
                filename = f"parecer_{post_data.get('notificacao', 'teste').replace('/', '_')}.pdf"

            elif parsed_path.path == '/gerar_regimento':
                # Compila regimento interno
                pdf_bytes, elapsed_ms = run_typst('regimento.typ', post_data if post_data else None)
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
            self._send_json(500, {
                'status': 'error',
                'message': str(err),
                'engine': 'Typst CLI (Porta 5050)'
            })


def main():
    print(f"Servidor Typst API iniciado na porta {PORT}...")
    print(f"Usando binário Typst: {TYPST_BIN}")
    print(f"Templates em: {TEMPLATES_DIR}")
    server = HTTPServer(('0.0.0.0', PORT), TypstHandler)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nEncerrando servidor Typst API...")
        server.server_close()


if __name__ == '__main__':
    main()
