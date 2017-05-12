# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

# 1.1.3 - 2017-05-12
### Added
* get page status function

# 1.1.2 - 2017-05-10
### Added
* Fix listFiles
* base_url()
### Changed
* Updated SingletonTrait Doc for a right autocompletion.

# 1.1.1 - 2017-05-04
### Added
* Data Retreiver Helper
* getLocalData function
* getRemoteData function
* downloadFile function
* listFiles function

# 1.1.0 - 2017-03-14
### Added
* Database class can now import from compressed archive.
* File class can now perform deployment or withdrawal.
* New Composer custom installer for Wordpress and Drupal CMS, you can now use composer type `rocket-cms` to migrate a package to cms folder. ( default : `web/edition/` ).
* Added Composer checking version.
### Changed
* Composer System Installer is now deprecated and replaced by Composer Installer which is now placed on boilerplate.
* Application environment is now set to production by default
* Clean function has been removed from composer install
* Complete code review for standard coding syntax.

# 1.0.4 - 2017-01-31
### Changed
* Removed node modules install when using "composer install"
### Added
* node modules install when using "composer build"


# 1.0.3 - 2017-01-25
### Added
* Composer scripts
    * database/import : Import sql file in mysql
    * database/export : export to app/backup
    * archive/extract : extract backup file to any path
    * archive/create : tar -xvzf folder
    * build : shortcut `cd /app/resources/builder && gulp -p`
    * create : Assets modules creation
* Documentation improvements
* Git LFS Support at composer installation.


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
