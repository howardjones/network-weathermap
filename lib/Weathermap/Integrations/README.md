# Host Application Integration

## Introduction

As much as possible, the web UI is shared between host applications (Cacti, LibreNMS, etc).
To do this, the database tables for weathermap itself are expected to be the same between
host apps. The table names all have a `weathermap_` prefix, so it's unlikely that there will
be a conflict with anything used by the host.

There are some other assumptions made about the host application (or the interface class in subdirectories here, see below):

* can take an integer user id and map it to a displayable user name.
* can take an integer user group id and map it to a displayable user name(or just ignore it, and return an empty group list).
* can get, set, and create name-value settings on behalf of weathermap

## ApplicationInterface class

This class is responsible for mapping functionality from a sort-of idealised host application
into whatever function calls or database queries are necessary to get the relevant information.

This is mainly used for things like application settings, and getting user details.

## Management REST API

This is used by the management web app to deal with most operations on the map database.

* **listmaps** Get a list of scheduled maps with all configuration information, and list of all map groups.

* **listmapfiles** Get a list of all config files in the configs/ sub directory, along with flags to indicate if they are already in use (in the list of scheduled maps) 

* **app_settings** Get a dictionary of the basic settings needed by the web app to get started (API root URL, URLs for other components)

* **list_users** Get a list of all users
 
* **list_usergroups** Get a list of user groups

* **map_create** Create a new map config file (and optionally add to schedule)

* **map_add** Add an existing map config file to the schedule

* **map_delete** Remove a map from the schedule

* **map_update**

* **map_getconfig** Get the text contents of a map configuration file  

* **group_add**

* **group_delete**

* **group_update**

* **settings_add** Add a 'map setting' to either the global, group or map scope.

* **settings_delete** Delete a 'map setting' from either the global, group or map scope.

* **settings_update** Update the value of an existing 'map setting' in either the global, group or map scope.

* **perms_add**

* **perms_delete**

* **perms_update**


## User-facing REST API

This is used by the 'user' web app to show the user their relevant maps in various ways.

