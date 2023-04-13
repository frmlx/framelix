/**
 * Framelix lang/translations
 */
class FramelixLang {

  /**
   * Translations values
   * @type {Object<string, Object<string, string>>}
   */
  static values = {}

  /**
   * Language files that are ready to be loaded
   * @type {Object}
   */
  static loadableLangFiles = {}

  /**
   * Internal promises for loadable lang files
   * @type {Object}
   */
  static loadingPromises = {}

  /**
   * Available languages
   * @var {string[]}
   */
  static languagesAvailable

  /**
   * The active language
   * @var {string}
   */
  static lang

  /**
   * The fallback language when a key in active language not exist
   * @var {string}
   */
  static langFallback

  /**
   * Get translated language key
   * @param {string|array} key If array then array values represent all possible parameters from this function
   *  0 => key
   *  1 => parameters
   *  2 => lang
   * @param {Object=} parameters
   * @param {string=} lang
   * @return {Promise<string>}
   */
  static async get (key, parameters, lang) {
    if (Array.isArray(key)) {
      return FramelixLang.get.apply(null, key)
    }
    if (!key || typeof key !== 'string' || !key.startsWith('__')) {
      return key
    }
    const langDefault = lang || FramelixLang.lang
    const langFallback = FramelixLang.langFallback
    const languages = [langDefault, langFallback]
    for (let loadLang of languages) {
      if (!FramelixLang.loadingPromises[loadLang]) {
        FramelixLang.loadingPromises[loadLang] = new Promise(async function (resolve) {
          if (!FramelixLang.values[loadLang]) {
            FramelixLang.values[loadLang] = {}
          }
          if (!FramelixLang.loadableLangFiles[loadLang]) {
            resolve()
            return
          }
          for (let key in FramelixLang.loadableLangFiles[loadLang]) {
            const row = FramelixLang.loadableLangFiles[loadLang][key]
            FramelixLang.values[loadLang] = Object.assign(FramelixLang.values[loadLang], await (await fetch(row.url)).json())
          }
          resolve()
        })
      }
      await FramelixLang.loadingPromises[loadLang]
    }
    let value = null
    if (FramelixLang.values[langDefault] && FramelixLang.values[langDefault][key] !== undefined) {
      value = FramelixLang.values[langDefault][key]
    }
    if (value === null && FramelixLang.values[langFallback] && FramelixLang.values[langFallback][key] !== undefined) {
      value = FramelixLang.values[langFallback][key]
    }
    if (value === null) {
      return key
    }
    if (Array.isArray(value)) value = value[0]
    if (parameters) {
      // replace conditional parameters
      let re = /\{\{(.*?)\}\}/ig
      let m
      do {
        m = re.exec(value)
        if (m) {
          let replaceWith = null
          let conditions = m[1].split('|')
          for (let i = 0; i < conditions.length; i++) {
            const condition = conditions[i]
            const conditionSplit = condition.match(/^([a-z0-9-_]+)([!=<>]+)([0-9*]+):(.*)/i)
            if (conditionSplit) {
              const parameterName = conditionSplit[1]
              const compareOperator = conditionSplit[2]
              const compareNumber = parseInt(conditionSplit[3])
              const outputValue = conditionSplit[4]
              const parameterValue = parameters[parameterName]
              if (conditionSplit[3] === '*') {
                replaceWith = outputValue
              } else if (compareOperator === '=' && compareNumber === parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '<' && compareNumber < parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '>' && compareNumber > parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '<=' && compareNumber <= parameterValue) {
                replaceWith = outputValue
              } else if (compareOperator === '>=' && compareNumber >= parameterValue) {
                replaceWith = outputValue
              }
              if (replaceWith !== null) {
                break
              }
            }
          }
          value = FramelixStringUtils.replace(m[0], replaceWith === null ? '' : replaceWith, value)
        }
      } while (m)

      // replace normal parameters
      for (let search in parameters) {
        let replace = parameters[search]
        value = FramelixStringUtils.replace('{' + search + '}', replace, value)
      }
    }
    return value
  }
}