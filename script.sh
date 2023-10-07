#!/bin/bash

echo "Este é um script executado como root."
# Adicione aqui os comandos que precisam de privilégios de root.
# Verifique se um comando foi passado como argumento
if [ -z "$1" ]; then
    echo "Uso: $0 <comando>"
    exit 1
fi

# Execute o comando passado como parâmetro
comando="$1"
$comando
