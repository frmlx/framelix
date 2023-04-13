/**
 * Framelix configuration
 */
class FramelixConfig {

  /**
   * The url to the base of the application
   * @type {string}
   */
  static applicationUrl

  /**
   * The url to the public folder of current module
   * @type {string}
   */
  static modulePublicUrl

  /**
   * All registered framework modules
   * @type {string[]}
   */
  static modules

  /**
   * The compiled file urls
   * @type {Object}
   */
  static compiledFileUrls = {}

  /**
   * The human-readable default dateTime format in PHP
   * @type {string}
   */
  static dateTimeFormatPhp

  /**
   * The human-readable default date format in PHP
   * @type {string}
   */
  static dateFormatPhp

  /**
   * The human-readable default dateTime format in javascript
   * @type {string}
   */
  static dateTimeFormatJs

  /**
   * The human-readable default date format in javascript
   * @type {string}
   */
  static dateFormatJs

}