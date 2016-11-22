# -*- mode: ruby -*-
# # vi: set ft=ruby :
#
# # UTC        for Universal Coordinated Time
# # EST        for Eastern Standard Time
# # US/Central for American Central
# # US/Eastern for American Eastern
VAGRANTFILE_API_VERSION          = "2"
server_timezone                  = "PST"
hostname                         = "Innobackupex"
server_ip                        = "3.3.3.4"
box                              = "https://cloud-images.ubuntu.com/vagrant/trusty/current/trusty-server-cloudimg-amd64-vagrant-disk1.box"
shared_directory                 = "/var/www/"

#
#
if (/darwin/ =~ RUBY_PLATFORM) != nil # mac
  server_memory                    = `sysctl -n hw.memsize |   awk '{$2=$1/(1024^2)/2; print $2}'`.chomp # one half of available system memory
  server_swap                      = `sysctl hw.memsize  | awk '{$2=$2/(1024^2)/8; print $2}'`.chomp # one eighth of memory
  server_cpu                       = `sysctl hw.ncpu | awk '{$2=$2/(2); print $2}'`.chomp # half of cpus
else #linux hopefully
  server_memory                    = `grep 'MemTotal' /proc/meminfo |   sed -e 's/MemTotal://' |  awk '{$1=$1/(1024)/2;  } END  { printf("%.0f", $1) }'`.chomp # one half of available system memory
  server_swap                      = `grep 'MemTotal' /proc/meminfo |   sed -e 's/MemTotal://' |  awk '{$1=$1/(1024)/8;  } END  { printf("%.0f", $1) }'`.chomp # one eighth of memory
  server_cpu                       = `nproc | awk '{$1=$1/(2); print $1}'`.chomp # half of cpus
end

ansible_host                     = "vagrant"
ansible_repo_directory            = "ansible/"
ENV['ANSIBLE_CONFIG']            = ansible_repo_directory + "ansible.cfg"
ansible_inventory_file           = ansible_repo_directory + "hosts"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # All Vagrant configuration is done here. The most common configuration
  # options are documented and commented below. For a complete reference,
  # please see the online documentation at vagrantup.com.

  # Every Vagrant virtual environment requires a box to build off of.
  config.vm.box = box
  #config.ssh.private_key_path = private_key
  #config.vm.network :, ip: server_ip, adapter: "2"
  config.vm.network :forwarded_port, guest: 22, host: 2221, id: 'ssh'
  
  config.vm.network :private_network, ip: server_ip, auto_config: true

  config.vm.synced_folder "./", shared_directory, :owner=> 'vagrant', :group=>'vagrant', :mount_options => ['dmode=777','fmode=777']

   #config.ssh.private_key_path = "./vagrant_insecure"
   #
   preferred_interfaces = ['en1', 'en1: Wi-Fi (AirPort)', 'Wi-Fi', 'Thunderbolt 1', 'Thunderbolt 2']

  # If using VirtualBox
  config.vm.provider :virtualbox do |vb|

    vb.gui = true
    vb.name = "Innobackupex"

    vb.customize ["modifyvm", :id, "--rtcuseutc", "on"]

    vb.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/v-root", "1"]
    # Set server memory
    vb.customize ["modifyvm", :id, "--memory", server_memory]

    vb.customize ["modifyvm", :id, "--cpus", server_cpu]
    # Set the timesync threshold to 10 seconds, instead of the default 20 minutes.
    # If the clock gets more than 15 minutes out of sync (due to your laptop going
    # to sleep for instance, then some 3rd party services will reject requests.
    vb.customize ["guestproperty", "set", :id, "/VirtualBox/GuestAdd/VBoxService/--timesync-set-threshold", 10000]


    # Prevent VMs running on Ubuntu to lose internet connection
    # vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    # vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]

  end

  config.vm.provision "ansible" do |ansible|
    ansible.extra_vars      = { target: ansible_host, ansible_ssh_user: "vagrant", server_hostname: hostname }
    ansible.inventory_path  = ansible_inventory_file
    ansible.verbose         = "v"
    ansible.limit           = "all"
    ansible.playbook        = ansible_repo_directory+"provision_server.yml"
  end

  config.vm.provision "reset_mysql", run: "never", type: "ansible" do |ansible|
    ansible.extra_vars      = { target: ansible_host, ansible_ssh_user: "vagrant", server_hostname: hostname }
    ansible.inventory_path  = ansible_inventory_file
    ansible.verbose         = "v"
    ansible.limit           = "all"
    ansible.tags            = "reset_mysql"
    ansible.playbook        = ansible_repo_directory+"provision_server.yml"
  end
end
