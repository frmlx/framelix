/**
 * Framelix string utils
 */
class FramelixStringUtils {

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
    if (string.length <= length) return string
    return string.substr(0, length) + truncateAffix
  }
}