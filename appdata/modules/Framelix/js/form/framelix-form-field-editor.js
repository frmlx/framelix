/**
 * Editor field (TinyMCE)
 */
class FramelixFormFieldEditor extends FramelixFormField {

  static tinymceIncluded = false

  /**
   * The textarea element
   * @type {Cash}
   */
  textarea

  /**
   * The minimal height for the textarea in pixel
   * @type {number|null}
   */
  minHeight = null

  /**
   * The maximal height for the textarea in pixel
   * @type {number|null}
   */
  maxHeight = null

  /**
   * Spellcheck
   * @type {boolean}
   */
  spellcheck = false

  /**
   * Min length for submitted value
   * @type {number|string|null}
   */
  minLength = null

  /**
   * Max length for submitted value
   * @type {number|string|null}
   */
  maxLength = null

  /**
   * The editor instance
   * @type {Object}
   */
  editor

  /**
   * The path to the tinymce script file
   * @type {string}
   */
  tinymcePath

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    if (this.textarea.val() === value) {
      return
    }
    this.textarea.val(value)
    this._editor?.setContent(value)
    this.triggerChange(this.textarea, isUserChange)
  }

  /**
   * Get value for this field
   * @return {string}
   */
  getValue () {
    return this.textarea.val()
  }

  /**
   * Initialize tinymce editor on given textarea element
   * @param {Cash} element
   * @param {number=} height If set then use fixed height instead of autoresize
   * @return {Promise<void>}
   */
  async initializeTinymce (element, height) {
    const self = this
    return new Promise(async function (resolve) {
      if (!FramelixFormFieldEditor.tinymceIncluded) {
        FramelixFormFieldEditor.tinymceIncluded = true
        await FramelixDom.includeResource(self.tinymcePath, 'tinymce')
      }
      let plugins = [
        'advlist', 'autolink', 'lists', 'image', 'link',
        'searchreplace', 'visualblocks', 'code',
        'table', 'code'
      ]
      const darkMode = $('html').attr('data-color-scheme') === 'dark'
      if (!height) plugins.push('autoresize')
      tinymce.init({
        target: element[0],
        language: FramelixLang.lang,
        browser_spellcheck: self.spellcheck,
        'height': height,
        menubar: 'edit insert view format table tools',
        statusbar: false,
        readonly: self.disabled,
        pagebreak_separator: '<div class="framelix-form-field-editor-pagebreak" pagebreak="true"></div>',
        pagebreak_split_block: true,
        ui_mode: 'split',
        'plugins': plugins,
        contextmenu: 'copy | paste | link image inserttable | cell row column deletetable',
        toolbar: 'insert | undo redo | fontsizeselect | bold italic underline forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | placeholders textconditions pagebreak',
        content_css: darkMode ? 'dark' : 'default',
        skin: darkMode ? 'oxide-dark' : 'oxide',
        autoresize_bottom_margin: 10,
        paste_as_text: true,
        paste_block_drop: true,
        relative_urls: false,
        remove_script_host: false,
        promotion: false,
        min_height: self.minHeight,
        max_height: self.maxHeight,
        init_instance_callback: function (editor) {
          tinymce.triggerSave()
          editor.on('change', function () {
            editor.save()
          })
          self.editor = editor
          resolve(editor)
        },
        setup: function (editor) {
        }
      })
    })
  }

  /**
   * Validate
   * Return error message on error or true on success
   * @return {Promise<string|true>}
   */
  async validate () {
    if (!this.isVisible()) return true

    const parentValidation = await super.validate()
    if (parentValidation !== true) return parentValidation

    const value = this.getValue()
    if (this.minLength !== null) {
      if (value.length < this.minLength) {
        return await FramelixLang.get('__framelix_form_validation_minlength__', { 'number': this.minLength })
      }
    }

    if (this.maxLength !== null) {
      if (value.length > this.maxLength) {
        return await FramelixLang.get('__framelix_form_validation_maxlength__', { 'number': this.maxLength })
      }
    }

    return true
  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.textarea = $(`<textarea></textarea>`)
    this.field.html(this.textarea)
    this.textarea.attr('name', this.name)
    this.textarea.val(this.defaultValue || '')
    await this.initializeTinymce(this.textarea)
  }
}

FramelixFormField.classReferences['FramelixFormFieldEditor'] = FramelixFormFieldEditor