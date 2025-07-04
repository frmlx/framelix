/**
 * Color utils for some color converting jobs
 */
class FramelixColorUtils {

  /**
   * Internal cache for invertColor if it required color computing
   * @type {{}}
   * @private
   */
  static inverterCache = {}

  /**
   * Set colors on FramelixHtmlAttributes based on the color definition
   * @param {FramelixTypeDefElementColor|Object|string} colorDef
   * @param {FramelixHtmlAttributes} attributes
   * @param {string} tagName For which html tag is it, example: framelix-button, div, etc..
   */
  static setColorHtmlAttributesFromColorDef (colorDef, attributes, tagName) {
    if (typeof colorDef === 'string' || colorDef.theme) {
      const theme = colorDef.theme || colorDef
      // custom elements have themes built-in
      if (tagName.startsWith('framelix-')) {
        attributes.set('theme', theme)
      } else {
        attributes.set('data-theme', theme)
        attributes.setStyle('color', 'var(--color-' + theme + '-text)')
        attributes.setStyle('background-color', 'var(--color-' + theme + '-text)')
      }
    }
    if (colorDef instanceof FramelixTypeDefElementColor || typeof colorDef === 'object') {
      if (colorDef.bgColor) {
        if (typeof colorDef.bgColor === 'string') {
          attributes.setStyle('background-color', colorDef.bgColor)
        } else if (FramelixDom.getNativeElement(colorDef.bgColor)) {
          attributes.setStyle('background-color', $(colorDef.bgColor).css('background-color'))
        } else {
          const hsla = colorDef.bgColor
          const colorType = hsla.length === 4 ? 'hsla' : 'hsl'
          if (hsla.length < 2 || hsla[1] === null) {
            hsla[1] = 'var(--color-default-contrast-bg)'
          } else {
            hsla[1] += '%'
          }
          if (hsla.length < 3 || hsla[2] === null) {
            hsla[2] = 'var(--color-default-lightness-bg)'
          } else {
            hsla[2] += '%'
          }
          attributes.setStyle('background-color', colorType + '(' + hsla.join(', ') + ')')
        }
      }
      if (colorDef.textColor) {
        if (colorDef.textColor === 'invert') {
          if (!colorDef.bgColor) {
            console.error('To invert textcolor, a bgcolor must be set')
            return
          }
          attributes.setStyle('background-color', FramelixColorUtils.invertColor(colorDef.bgColor, true))
        } else if (typeof colorDef.textColor === 'string') {
          attributes.setStyle('color', colorDef.textColor)
        } else if (FramelixDom.getNativeElement(colorDef.textColor)) {
          attributes.setStyle('color', $(colorDef.textColor).css('color'))
        } else {
          const hsla = colorDef.textColor
          const colorType = hsla.length === 4 ? 'hsla' : 'hsl'
          if (hsla.length < 3) {
            hsla[2] = 'var(--color-default-lightness-text)'
          }
          if (hsla.length < 2) {
            hsla[1] = 'var(--color-default-contrast-text)'
          }
          hsla[1] += '%'
          hsla[2] += '%'
          attributes.setStyle('color', colorType + '(' + hsla.join(', ') + ')')
        }
      }
    }
  }

  /**
   * Set colors on an element based on the color definition
   * @param {FramelixTypeDefElementColor|Object|string} colorDef
   * @param {HTMLElement|Cash} element
   */
  static setColorsFromElementColorDef (colorDef, element) {
    element = $(element)
    if (typeof colorDef === 'string' || colorDef.theme) {
      const theme = colorDef.theme || colorDef
      // custom elements have themes built-in
      if (element[0].tagName.startsWith('FRAMELIX-')) {
        element.attr('theme', theme)
      } else {
        // if provided a data-theme attribute, it is considered to can handle themes
        if (typeof element.attr('data-theme') === 'string') {
          element.attr('data-theme', theme)
        } else {
          element.css('color', 'var(--color-' + theme + '-text)').css('background-color', 'var(--color-' + theme + '-text)')
        }
      }
    }
    if (colorDef instanceof FramelixTypeDefElementColor || typeof colorDef === 'object') {
      if (colorDef.bgColor) {
        if (typeof colorDef.bgColor === 'string') {
          element.css('background-color', colorDef.bgColor)
        } else if (FramelixDom.getNativeElement(colorDef.bgColor)) {
          element.css('background-color', $(colorDef.bgColor).css('background-color'))
        } else {
          const hsla = colorDef.bgColor
          const colorType = hsla.length === 4 ? 'hsla' : 'hsl'
          if (hsla.length < 2 || hsla[1] === null) {
            hsla[1] = 'var(--color-default-contrast-bg)'
          } else {
            hsla[1] += '%'
          }
          if (hsla.length < 3 || hsla[2] === null) {
            hsla[2] = 'var(--color-default-lightness-bg)'
          } else {
            hsla[2] += '%'
          }
          element.css('background-color', colorType + '(' + hsla.join(', ') + ')')
        }
      }
      if (colorDef.textColor) {
        if (colorDef.textColor === 'invert') {
          element[0].style.color = FramelixColorUtils.invertColor(element[0].backgroundColor, true)
        } else if (typeof colorDef.textColor === 'string') {
          element.css('color', colorDef.textColor)
        } else if (FramelixDom.getNativeElement(colorDef.textColor)) {
          element.css('color', $(colorDef.textColor).css('color'))
        } else {
          const hsla = colorDef.textColor
          const colorType = hsla.length === 4 ? 'hsla' : 'hsl'
          if (hsla.length < 3) {
            hsla[2] = 'var(--color-default-lightness-text)'
          }
          if (hsla.length < 2) {
            hsla[1] = 'var(--color-default-contrast-text)'
          }
          hsla[1] += '%'
          hsla[2] += '%'
          element.css('color', colorType + '(' + hsla.join(', ') + ')')
        }
      }
    }
  }

  /**
   * Invert any valid css color, including variables with var()
   * This returns black/white hex color, depending on given background color
   * @link https://stackoverflow.com/a/35970186/1887622
   * @param {string} colorStr
   * @param {boolean} blackWhite If true, then only return black or white, depending on which has better contrast
   * @return {string}
   */
  static invertColor (colorStr, blackWhite = false) {
    const cacheKey = colorStr + '_' + (blackWhite ? '1' : '0')
    if (typeof FramelixColorUtils.inverterCache[cacheKey] !== 'undefined') {
      return FramelixColorUtils.inverterCache[cacheKey]
    }
    let rgb = []
    if (colorStr.startsWith('#') && colorStr.length === 7) {
      rgb = FramelixColorUtils.hexToRgb(colorStr)
    } else if (colorStr.startsWith('rgb')) {
      rgb = colorStr.replace(/[^0-9.,]/g, '').split(',').map(function (value) {
        return parseInt(value)
      })
    } else if (colorStr.startsWith('hsl')) {
      rgb = FramelixColorUtils.hslToRgb(...colorStr.replace(/[^0-9.,]/g, '').split(',').map(function (value, index) {
        if (index === 0) {
          return parseFloat(value) / 360
        }
        return parseFloat(value) / 100
      }))
    } else {
      const el = document.createElement('span')
      el.style.color = colorStr
      document.body.appendChild(el)
      FramelixColorUtils.inverterCache[cacheKey] = FramelixColorUtils.invertColor(getComputedStyle(el).color)
      el.remove()
      return FramelixColorUtils.inverterCache[cacheKey]
    }
    if (blackWhite) {
      // https://stackoverflow.com/a/3943023/112731
      FramelixColorUtils.inverterCache[cacheKey] = (rgb[0] * 0.299 + rgb[1] * 0.587 + rgb[2] * 0.114) > 186 ? '#000' : '#fff'
      return FramelixColorUtils.inverterCache[cacheKey]
    }
    // invert color components
    let r = (255 - rgb[0]).toString(16)
    let g = (255 - rgb[1]).toString(16)
    let b = (255 - rgb[2]).toString(16)
    FramelixColorUtils.inverterCache[cacheKey] = '#' + r.padStart(2, '0') + g.padStart(2, '0') + b.padStart(2, '0')
    return FramelixColorUtils.inverterCache[cacheKey]
  }

  /**
   * Converts an HSL color value to RGB. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes h, s, and l are contained in the set [0, 1] and
   * returns r, g, and b in the set [0, 255].
   * @link  https://stackoverflow.com/a/9493060/1887622
   * @param   {number}  h
   * @param   {number}  s
   * @param   {number}  l
   * @return  {number[]}
   */
  static hslToRgb (h, s, l) {
    let r, g, b

    if (s === 0) {
      r = g = b = l // achromatic
    } else {
      const hue2rgb = function hue2rgb (p, q, t) {
        if (t < 0) {
          t += 1
        }
        if (t > 1) {
          t -= 1
        }
        if (t < 1 / 6) {
          return p + (q - p) * 6 * t
        }
        if (t < 1 / 2) {
          return q
        }
        if (t < 2 / 3) {
          return p + (q - p) * (2 / 3 - t) * 6
        }
        return p
      }

      let q = l < 0.5 ? l * (1 + s) : l + s - l * s
      let p = 2 * l - q
      r = hue2rgb(p, q, h + 1 / 3)
      g = hue2rgb(p, q, h)
      b = hue2rgb(p, q, h - 1 / 3)
    }
    return [FramelixNumberUtils.round(r * 255, 0), FramelixNumberUtils.round(g * 255, 0), FramelixNumberUtils.round(b * 255, 0)]
  }

  /**
   * Converts an RGB color value to HSL. Conversion formula
   * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
   * Assumes r, g, and b are contained in the set [0, 255] and
   * returns h, s, and l in the set [0, 1].
   * @link  https://stackoverflow.com/a/9493060/1887622
   * @param   {number}  r
   * @param   {number}  g
   * @param   {number}  b
   * @return  {number[]}
   */
  static rgbToHsl (r, g, b) {
    r /= 255
    g /= 255
    b /= 255
    let max = Math.max(r, g, b), min = Math.min(r, g, b)
    let h, s, l = (max + min) / 2

    if (max === min) {
      h = s = 0 // achromatic
    } else {
      let d = max - min
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min)
      switch (max) {
        case r:
          h = (g - b) / d + (g < b ? 6 : 0)
          break
        case g:
          h = (b - r) / d + 2
          break
        case b:
          h = (r - g) / d + 4
          break
      }
      h /= 6
    }
    return [h, s, l]
  }

  /**
   * Hex to RGB
   * @link https://stackoverflow.com/a/5624139/1887622
   * @param {string} hex
   * @return {number[]|null}
   */
  static hexToRgb (hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? [
      parseInt(result[1], 16),
      parseInt(result[2], 16),
      parseInt(result[3], 16)
    ] : null
  }

  /**
   * RGB to hex
   * @param {number} r
   * @param {number} g
   * @param {number} b
   * @return {string}
   */
  static rgbToHex (r, g, b) {
    return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)
  }

  /**
   * Convert a rgb() css color string into hex
   * @param {string|null} rgb
   * @return {string}
   */
  static cssColorToHex (rgb) {
    const arr = (rgb || null).replace(/[^0-9.,]/g, '').split(',')
    return FramelixColorUtils.rgbToHex(parseFloat(arr[0] || 0), parseFloat(arr[1] || 0), parseFloat(arr[2] || 0))
  }
}