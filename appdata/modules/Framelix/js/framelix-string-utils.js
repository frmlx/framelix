/**
 * Framelix string utils
 */
class FramelixStringUtils {

  /**
   * Converts data with JSON.stringify() but converts special characters into unicode standard (Same as Php->JsonUtils::encode(convertSpecialChars:true)
   * Mimics same behaviour as JsonUtils::encode() in PHP
   * Output is still a valid JSON string
   * Converted chars are: ", ', &, <, >
   * @param {*} data
   * @param {boolean} prettyPrint If true, it adds whitespace and newlines to the json output
   * @param {boolean} convertSpecialChars If true, convert html special chars to unicode representation
   * @return {string}
   */
  static jsonStringify (data, prettyPrint = false, convertSpecialChars = false) {
    let str = JSON.stringify(data, null, prettyPrint ? 2 : null)
    if (convertSpecialChars) {
      str = str.replace(/\\"/g, '\\u0022')
      str = str.replace(/'/g, '\\u0027')
      str = str.replace(/</g, '\\u003C')
      str = str.replace(/>/g, '\\u003E')
      str = str.replace(/&/g, '\\u0026')
    }
    return str
  }

  /**
   * Creates a slug out of a string.
   * Replaces everything but letters and numbers with dashes.
   * @see http://en.wikipedia.org/wiki/Slug_(typesetting)
   * @param {string} str The string to slugify.
   * @param {boolean} replaceSpaces
   * @param {boolean} replaceDots
   * @param {RegExp} replaceRegex
   * @return string A search-engine friendly string that is safe
   *   to be used in URLs.
   */
  static slugify (str, replaceSpaces = true, replaceDots = true, replaceRegex = /[^a-z0-9. \-_]/ig) {
    let s = ['Ö', 'Ü', 'Ä', 'ö', 'ü', 'ä', 'ß']
    let r = ['Oe', 'Ue', 'Ae', 'oe', 'ue', 'ae', 'ss']
    if (replaceSpaces) {
      s.push(' ')
      r.push('-')
    }
    if (replaceDots) {
      s.push('.')
      r.push('-')
    }
    for (let i = 0; i < s.length; i++) {
      str = str.replace(new RegExp(FramelixStringUtils.escapeRegex(s[i]), 'g'), r[i])
    }
    str = str.replace(replaceRegex, '-')
    return str.replace(/-{2,99}/i, '')
  }

  /**
   * Convert any value into a string
   * @param {*} value
   * @param {string} concatChar If is array/object, concat with this char
   * @return {string}
   */
  static stringify (value, concatChar = ', ') {
    if (value === null || value === undefined) {
      return ''
    }
    if (typeof value === 'object') {
      const arr = []
      for (let i in value) {
        arr.push(FramelixStringUtils.stringify(value[i], concatChar))
      }
      return arr.join(concatChar)
    }
    if (typeof value === 'boolean') {
      return value ? '1' : '0'
    }
    if (typeof value !== 'string') {
      return value.toString()
    }
    return value
  }

  /**
   * Replace all occurences for search with replaceWith
   * @param {string} search
   * @param {string} replaceWith
   * @param {string} str
   * @return {string}
   */
  static replace (search, replaceWith, str) {
    return (str + '').replace(new RegExp(FramelixStringUtils.escapeRegex(search), 'i'), replaceWith)
  }

  /**
   * Html escape a string
   * Also valid to use in html attributs
   * @param {string} str
   * @return {string}
   */
  static htmlEscape (str) {
    return (str + '').replace(/&/g, '&amp;').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  }

  /**
   * Escape str for regex use
   * @param {string} str
   * @return {string}
   */
  static escapeRegex (str) {
    return (str + '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  }

  /**
   * Cut a string at specific length and add $truncateAffix if too long
   * @param {string} string
   * @param {int} length
   * @param {string} truncateAffix
   * @return {string}
   */
  static cut (string, length, truncateAffix = '...') {
    if (string.length <= length) {
      return string
    }
    return string.substring(0, length) + truncateAffix
  }

  /**
   * Convert a string into HEX representation
   * @param {string} str
   * @returns {string}
   */
  static strToHex (str) {
    return str.split('').map(x => x.charCodeAt(0).toString(16).padStart(2, '0')).join('')
  }

  /**
   * Convert a HEX representation to actual string representation
   * @param {string} str
   * @returns {string}
   */
  static hexToStr (str) {
    return str.match(/.{1,2}/g).map(x => String.fromCharCode(parseInt(x, 16))).join('')
  }

}