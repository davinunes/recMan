#!/usr/bin/python3

from paramiko import SSHClient, RSAKey
import paramiko
import sys
import os

COMANDO = sys.argv[1]

class SSH:
    def __init__(self):
        self.ssh = SSHClient()
        self.ssh.load_system_host_keys()
        self.ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        # Localiza a chave SSH no mesmo diretório do script (independente de /var/www/html ou /var/www/mini)
        script_dir = os.path.dirname(os.path.abspath(__file__))
        key_file = os.path.join(script_dir, 'mykeyopenssh.pem')
        if not os.path.exists(key_file):
            key_file = '/var/www/html/py/mykeyopenssh.pem'
            
        self.key = RSAKey.from_private_key_file(key_file)
        self.ssh.connect(hostname='127.0.0.1', port='22', username='root', pkey=self.key)

    def exec_cmd(self, cmd):
        print(f"[SSH CMD] Enviando comando: {cmd}")
        stdin, stdout, stderr = self.ssh.exec_command(cmd)
        exit_code = stderr.channel.recv_exit_status()
        out_text = stdout.read().decode().strip()
        err_text = stderr.read().decode().strip()
        
        print(f"[SSH EXIT CODE] {exit_code}")
        result = []
        if out_text:
            result.append("[SSH STDOUT]\n" + out_text)
        if err_text:
            result.append("[SSH STDERR]\n" + err_text)
        if not result:
            result.append(f"[SSH EMPTY] Nenhuma saída retornada (exit code {exit_code}).")
            
        print("\n".join(result))

if __name__ == '__main__':
    try:
        print(f"[SSH START] Iniciando conexão SSH no script py/ssh.py...")
        ssh = SSH()
        print("[SSH CONNECTED] Conectado ao SSH 127.0.0.1 como root com sucesso.")
        ssh.exec_cmd(COMANDO)
    except Exception as e:
        print(f"[SSH EXCEPTION] {type(e).__name__}: {str(e)}")
