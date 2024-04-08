/**
 * The base type definition with helpful functions
 */
class FramelixBaseTypeDef {

  /**
   * Convert object to a str to use in an html attribute safely
   * @param {Object} data
   * @return {string}
   */
  static toAttrValue (data) {
    return FramelixStringUtils.jsonStringify(data, false, true)
  }

  /**
   * Convert html attribute value to object
   * @param {string} str
   * @return {*}
   */
  static fromAttrValue (str) {
    if (!str) {
      return null
    }
    return JSON.parse(str)
  }
}