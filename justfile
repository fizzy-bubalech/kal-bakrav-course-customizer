_default: 
  @just -l
[working-directory: '/home/ast/Projects/kalbakrav.co.il']
up:
    sudo systemctl start docker
    ddev start

down:
    cd ~/Projects/kalbakrav.co.il

    ddev stop
    sudo systemctl stop docker
    sudo systemctl stop docker.socket

restart:
    cd ~/Projects/kalbakrav.co.il
    ddev restart
