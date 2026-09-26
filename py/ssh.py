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
        stdin, stdout, stderr = self.ssh.exec_command(cmd)
        if stderr.channel.recv_exit_status() != 0:
            print(stderr.read().decode())  # Modificado para Python 3
        else:
            print(stdout.read().decode())  # Modificado para Python 3

if __name__ == '__main__':
    try:
        ssh = SSH()
        ssh.exec_cmd(COMANDO)
    except Exception as e:
        print(f"Erro de execução SSH (py/ssh.py): {str(e)}")
