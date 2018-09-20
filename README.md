# Maleficarum Storage

This component replaces the now obsolete Maleficarum Database compoment (https://github.com/pharaun13/maleficarum-database). All MSSQL code was removed and persistence layers were decoupled from actual model objects. Additionally generic Redis model/collection repository was implemented and provided as basis for project specific implementations.

# Change Log

## [1.2.0] - 2018-09-20
### Added
- Added a way to retrieve the list of all shards of a specified type from the shard manager.
- Added an implementation of the scan method to the Redis connection object (it needs a reference call so the magic implementation was insufficient)
### Fixed
- Incorrect exception message when attempting to execute logic methods on disconnected redis connection objects.

## [1.1.0] - 2018-09-10
### Added
- Added a way to inject custom shard selectors to data repositories. Shard selection will no longer lie within the interests of data models.
- Bumped Maleficarum\Data dependency to 4.X+

## [1.0.2] - 2018-09-05
### Added
- Automatic parameter type detection when binding boolean parameters in Postgresql shards.

## [1.0.1] - 2018-09-05
### Fixed
- Fixed a bug that resulted in shard statements being shared across shard connections.

## [1.0.0] - 2018-08-27
### Added
- initial release of the component