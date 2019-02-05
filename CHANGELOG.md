# Changelog 
All notable changes for the Summary Fields extension will be noted here.

## [Unreleased]
### Changed

## [4.0.0] - 2019-02-05
### Added
 - This changelog.
 - If you are extending Summary Fields, you can now use trigger tables
   that don't have the contact_id field. You can specify a tables array
   containing the information needed for summary fields to properly
   process changes to these tables. See custom.php and civicrm_line_item
   table for an example.

### Changed 
 - All contribution summary fields are now calculated according to the
   CiviCRM line item table instead of the contribution table. That means
   contributions split between different financial types due to clever
   use of price fields will now be calculated properly
 - Improved documentation.
 - Don't count test participation records. 
