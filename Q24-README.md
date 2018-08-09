# Q24 Readme

Purpose of these changes are to create a development environment for our customer(s). This includes an out of the box working environment,
where (development) changes are reflected by cacti / weathermap in the easiest manner possible.

For this the vagrant setup mounts the weathermap project directory as the plugin directory in cacti. Changes in PHP code are (thus)
available directly. The HTML (HTML, JavaScript and CSS) have a watcher that reacts to file changes, so one command will suffice to make
front-end changes visible.


## Getting started
**Note** All directories are relative to the project's root, so if this project is checkout under `/foo/bar/q24-weathermap`, then `images`
is a directory just beneath the projects root. It's full path would be `/foo/bar/q24-weathermap/images`.

Prerequisites:
* VirtualBox (>5.x)
* Vagrant (>2.x)
* node (>8.x)

### File permissions
It is necessary to change the file permissions:
```bash
chmod -R oug+rwx configs
chmod -R oug+rwx output
```

### Start vagrant
From the `dev/Vagrant` directory, initialize the vagrant box:
```bash
$ vagrant up
```

## Development

Start vagrant, in `dev/Vagrant`:
```bash
$ vagrant up
```

Edit the files you need. For PHP code changes, they are available directly. For the front-end, you will need to run the build watchers:

In `websrc/cacti-mgmt`, run:
```bash
$ node run dev
```

In `websrc/cacti-user`, run:
```bash
$ node run dev
```

## Changes
1. Include CSS in weathermap plugin output.
1. Some styling changes for displaying groups as tabs.
1. Allow adding groups.
1. Allow removing of groups.
1. Allow adding of maps to a group.
1. Allow removing maps from groups.
