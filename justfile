_default: 
  @just -l

up:
    cd ~/Projects/kalbakrav.co.il
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
