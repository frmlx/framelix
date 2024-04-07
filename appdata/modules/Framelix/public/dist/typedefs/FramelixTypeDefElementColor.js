class FramelixTypeDefElementColor extends FramelixBaseTypeDef {
    /**
     * Default color theme, a somewhat bg/text
     */
    static THEME_DEFAULT = "default"

    /**
     * Primary color, a blue-ish color
     */
    static THEME_PRIMARY = "primary"

    /**
     * Success color, a green-ish color
     */
    static THEME_SUCCESS = "success"

    /**
     * Warning color, a orange/brown-sh color
     */
    static THEME_WARNING = "warning"

    /**
     * Error color, a red color
     */
    static THEME_ERROR = "error"

    /**
    * @param {FramelixTypeDefElementColor|Object} data
    * @return {string}
    */
    static toAttrValue (data) { return super.toAttrValue(data) }

    /**
    * @param {string} str
    * @return {FramelixTypeDefElementColor|Object|null}
    */
    static fromAttrValue (str) { return super.fromAttrValue(str) }

    /**
     * Predefined color theme for action colors like error, success, etc...
     * Require any of the class constants starting with THEME_
     * @type  {("default", "primary", "success", "warning", "error")}
     */
    theme = "default"

    /**
     * Background color in HSL range to override
     * Array of 4 numeric values, where only the first is required
     * 0 = Hue between 0-360
     * 1 = Saturation between 0-100 (Percent) (If not set, it uses saturation depending on dark/light mode)
     * 2 = Lightness between 0-100 (Percent) (If not set, it uses darkness depending on dark/light mode)
     * 3 = Alpha opacity between 0-1 (0=Invisible, If not set, it is fully visible)
     * @type  {number[]|null}
     */
    bgColor = null

    /**
     * Text color in HSL range to override
     * Array of 4 numeric values, where only the first is required
     * 0 = Hue between 0-360
     * 1 = Saturation between 0-100 (Percent) (If not set, it uses saturation depending on dark/light mode)
     * 2 = Lightness between 0-100 (Percent) (If not set, it uses darkness depending on dark/light mode)
     * 3 = Alpha opacity between 0-1 (0=Invisible, If not set, it is fully visible)
     * If given a string "invert" then it inverts the text color to white/black based on the best contrast with background
     * @type  {number[]|string|null}
     */
    textColor = null

}