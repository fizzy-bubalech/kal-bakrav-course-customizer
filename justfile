_default: 
  @just -l
[working-directory: '/home/ast/Projects/kalbakrav.co.il']
up:
    sudo systemctl start docker
    ddev start

[working-directory: '/home/ast/Projects/kalbakrav.co.il']
down:
    ddev stop
    sudo systemctl stop docker
    sudo systemctl stop docker.socket

[working-directory: '/home/ast/Projects/kalbakrav.co.il']
restart:
    ddev restart
