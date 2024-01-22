/**
 * Framelix date utils
 */
class FramelixDateUtils {

  /**
   * Compare given dates
   * @param {*} dateA
   * @param {*} dateB
   * @return{string} Return = for equal, < for A as lower than B, > for A as greather than B
   */
  static compare (dateA, dateB) {
    dateA = parseInt(FramelixDateUtils.anyToDayJs(dateA)?.format('YYYYMMDD') || 0)
    dateB = parseInt(FramelixDateUtils.anyToDayJs(dateB)?.format('YYYYMMDD') || 0)
    if (dateA < dateB) {
      return '<'
    }
    if (dateA > dateB) {
      return '>'
    }
    return '='
  }

  /**
   * Convert any given value to given format
   * @param {*} value
   * @param {string} outputFormat
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {string|null} Null of value is no valid date/time
   */
  static anyToFormat (value, outputFormat = FramelixConfig.dateFormatJs, expectedInputFormats = FramelixConfig.dateFormatJs + ',YYYY-MM-DD') {
    const instance = FramelixDateUtils.anyToDayJs(value, expectedInputFormats)
    if (instance === null) return null
    return instance.format(outputFormat)
  }

  /**
   * Convert any given value to a dayjs instance
   * @param {*} value
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {dayjs|null} Null of value is no valid date/time
   */
  static anyToDayJs (value, expectedInputFormats = FramelixConfig.dateFormatJs + ',YYYY-MM-DD') {
    if (value === null || value === undefined) return null
    // number is considered a unix timestamp
    if (typeof value === 'number') {
      return dayjs(value)
    }
    const instance = dayjs(value, expectedInputFormats.split(','))
    if (instance.isValid()) {
      return instance
    }
    return null
  }

  /**
   * Convert any given value to unixtime
   * @param {*} value
   * @param {string} expectedInputFormats
   * @see Format see https://day.js.org/docs/en/parse/string-format
   * @return {number|null} Null of value is no valid date/time
   */
  static anyToUnixtime (value, expectedInputFormats = FramelixConfig.dateFormatJs + ',YYYY-MM-DD') {
    const instance = FramelixDateUtils.anyToDayJs(value, expectedInputFormats)
    if (instance === null) return null
    return instance.unix()
  }
}