/**
 * Framelix time utils
 */
class FramelixTimeUtils {

  /**
   * Convert any value that contain a time into hours
   * @param {*} value
   * @return {number}
   */
  static toHours (value) {
    const number = FramelixTimeUtils.toSeconds(value)
    return FramelixNumberUtils.round(number / 3600, 4)
  }

  /**
   * Convert any value that contain a time into seconds
   * @param {*} value
   * @return {number}
   */
  static toSeconds (value) {
    if (typeof value === 'number') return parseInt(value.toString())
    if (typeof value !== 'string' || !value.length) return 0
    if (!value.includes(':')) {
      return parseInt(value)
    }
    const spl = value.split(':')
    return (parseInt(spl[0]) * 3600) + (parseInt(spl[1]) * 60) + (parseInt(spl[2] || '0'))
  }

  /**
   * Convert hours to time string
   * @param {number} hours
   * @param {boolean=} includeSeconds
   * @return {string}
   */
  static hoursToTimeString (hours, includeSeconds) {
    return FramelixTimeUtils.secondsToTimeString(FramelixNumberUtils.round(hours * 3600, 0), includeSeconds)
  }

  /**
   * Convert seconds to time string
   * @param {number} seconds
   * @param {boolean=} includeSeconds
   * @return {string}
   */
  static secondsToTimeString (seconds, includeSeconds) {
    if (typeof seconds !== 'number') return ''
    const hours = Math.floor(seconds / 3600).toString()
    const minutes = Math.floor(seconds / 60 % 60).toString()
    const restSeconds = Math.floor(seconds % 60).toString()
    return hours.padStart(2, '0') + ':' + minutes.padStart(2, '0') + (includeSeconds ? ':' + restSeconds.padStart(2, '0') : '')
  }
}