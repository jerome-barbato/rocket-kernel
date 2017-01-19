# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

# 1.0.2 - 2017-01-19
### Changed
* Replaced npm by yarn

## 1.0.1 - 2017-01-11 ##
Merging Rocket Tools to project
### Added
* Changed Singleton Model to Traits
* Added autoloader
* `git pull` command now starts composer with any commit.
### Changed 
* Composer now start gulp
* Composer now install NPM Dependencies
* Folder:create is refracted from `create-file` to `create-folder` and entries are now with permissions :


     "extra": {
         "create-folder":{
             "metabolism/rocket-wordpress": {
                 "web/wp/wp-content/uploads": "0777"
             }
         }
     }
* PHP Statics functions are now instantiated and managed by Installer PHP Class.
* Folder:create file option, it can be used like this : 


     "extra": {
         "create-file":{
             "metabolism/rocket-wordpress": [
                 "web/wp/wp-content/uploads"
             ]
         }
     }
     
     
## 1.0 - 2016-12-29 ##
Projet created