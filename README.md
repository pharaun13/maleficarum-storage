# Maleficarum Storage

This component replaces the now obsolete Maleficarum Database compoment (https://github.com/pharaun13/maleficarum-database). All MSSQL code was removed and persistence layers were decoupled from actual model objects. Additionally generic Redis model/collection repository was implemented and provided as basis for project specific implementations.

# Change Log

## [2.0.1] - 2018-09-05
### Fixed
- Fixed a bug that resulted in shard statements being shared across shard connections. 

## [2.0.0] - 2018-09-05
### Changed
- Component updated to work with Maleficarum\Ioc 3.X

## [1.0.1] - 2018-09-05
### Fixed
- Fixed a bug that resulted in shard statements being shared across shard connections.

## [1.0.0] - 2018-08-27
### Added
- initial release of the component