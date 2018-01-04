# Vagrantfile for Cacti

With vagrant installed, the files in this directory allow you to quickly test Weathermap 
with any recent version of Cacti (0.8.8x and 1.x) and PHP (5.6 and 7.1 are tested).

If you copy `settings.sh-sample` to `settings.sh` and modify it, then you can influence what versions are used.

## Startup

Once you have the versions selected in `settings.sh`, use `vagrant up` to create your new virtual machine. Once the provisioning process is complete (around 3-5 minutes), you
can access Cacti on http://localhost:8016/cacti/ and also connect to the server with `vagrant ssh`. When you are
done with the VM, you can delete it with `vagrant destroy` or pause it to revisit later with `vagrant suspend`.

## Notes

By default, a fresh VM has the poller cron job disabled in `/etc/cron.d/cacti`. It also has a completely default database - you'll end
up in the Installer process for Cacti. All the prerequisites should be met. However, if there is a file in this directory (which maps to `/vagrant` inside the VM) which is named
`cacti-{VERSION}-post-install.sql`, then it will be used instead of the default
cacti.sql that comes with Cacti, to create the database. You can use this to rebuild Cacti over and over with
users and hosts already defined, and skip the Installer. If you go through the Installer
process once, to get to the state you'd like to keep, you can run something like `mysqldump -ucactiuser -pcactiuser cacti > cacti-1.1.29-post-install.sql` inside the VM to save the database dump for next time. 

The database and root passwords are not randomised. No special effort is made to secure the Cacti installation. *DON'T* use this VM as a base for a production Cacti installation!
