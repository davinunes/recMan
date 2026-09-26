#!/bin/bash
# Script de Gerenciamento do Serviço API Typst (Porta 5050)

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Encerra qualquer processo ativo na porta 5050 ou referente ao typst_server.py
fuser -k 5050/tcp >/dev/null 2>&1 || true
pkill -9 -f typst_server.py >/dev/null 2>&1 || true
sleep 1

# Inicia o microserviço desacoplado em segundo plano
nohup python3 -u typst_server.py > typst_server.log 2>&1 </dev/null &

sleep 1

# Exibe a verificação do processo ativo
ps aux | grep typst_server.py | grep -v grep
