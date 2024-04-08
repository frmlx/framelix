/**
 * A date field
 */
class FramelixFormFieldDate extends FramelixFormFieldText {

  maxWidth = 150

  /**
   * Show the datepicker button
   * @type {boolean}
   */
  showDatepickerBtn = true

  /**
   * Min date for submitted value
   * SQL format YYYY-MM-DD
   * @type {string|null}
   */
  minDate = null

  /**
   * Max date for submitted value
   * SQL format YYYY-MM-DD
   * @type {string|null}
   */
  maxDate = null

  /**
   * The current datepicker popup
   * @type {FramelixPopup|null}
   */
  datepickerPopup = null

  /**
   * The current datepicker open button
   * @type {cash|null}
   */
  datepickerBtn = null

  /**
   * Set value for this field
   * @param {*} value
   * @param {boolean} isUserChange Indicates if this change was done because of an user input
   */
  setValue (value, isUserChange = false) {
    let originalVal = this.input.val()
    let date = FramelixDateUtils.anyToDayJs(value)
    if (date) {
      date = FramelixDateUtils.anyToFormat(date)
    } else {
      date = ''
    }
    if (originalVal !== date) {
      this.input.val(date)
      this.triggerChange(this.input, isUserChange)
    }
  }

  isDateValid (date) {
    const value = FramelixDateUtils.anyToDayJs(date)
    if (value) {
      if (this.minDate !== null) {
        if (FramelixDateUtils.compare(value, this.minDate) === '<') {
          return false
        }
      }

      if (this.maxDate !== null) {
        if (FramelixDateUtils.compare(value, this.maxDate) === '>') {
          return false
        }
      }
    }
    return true
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

    const value = FramelixDateUtils.anyToDayJs(this.getValue())
    if (value) {
      if (this.minDate !== null) {
        if (FramelixDateUtils.compare(value, this.minDate) === '<') {
          return await FramelixLang.get('__framelix_form_validation_mindate__', { 'date': FramelixDateUtils.anyToFormat(this.minDate) })
        }
      }

      if (this.maxDate !== null) {
        if (FramelixDateUtils.compare(value, this.maxDate) === '>') {
          return await FramelixLang.get('__framelix_form_validation_maxdate__', { 'date': FramelixDateUtils.anyToFormat(this.maxDate) })
        }
      }
    }

    return true
  }

  async showDatepicker () {

    async function renderCalendar () {
      const table = $(`
        <table>
            <thead>
                <tr>
                
                </tr>
            </thead>
            <tbody>
            
            </tbody>
        </table>        
      `)
      container.find('.framelix-form-field-date-popup-calendar').empty().append(table)

      const trHead = table.find('tr')
      const tbody = table.find('tbody')
      for (let i = 1; i <= 7; i++) {
        trHead.append(`<th>${await FramelixLang.get('__framelix_dayshort_' + i + '__')}</th>`)
      }

      let today = dayjs().format('YYYY-MM-DD')
      let monthNow = monthSelected.month()
      let weekCurrent = monthSelected.clone().date(1).isoWeekday(1)

      while (true) {
        const tr = $(`<tr></tr>`)
        tbody.append(tr)
        let dateNow = weekCurrent.clone()
        for (let i = 1; i <= 7; i++) {
          const td = $(`<td data-date="${dateNow.format('YYYY-MM-DD')}">${dateNow.date()}</td>`)
          tr.append(td)
          if (dateNow.format('YYYY-MM-DD') === dateSelected.format('YYYY-MM-DD')) {
            td.attr('data-selected', '1')
          }
          if (monthNow !== dateNow.month()) {
            td.attr('data-other-month', '1')
          }
          if (dateNow.isoWeekday() >= 6) {
            td.attr('data-satsun', '1')
          }
          if (!self.isDateValid(dateNow)) {
            td.attr('data-disabled', '1')
          }
          if (today === dateNow.format('YYYY-MM-DD')) {
            td.attr('data-today', '1')
          }
          dateNow = dateNow.add(1, 'day')
        }
        weekCurrent = weekCurrent.add(1, 'week')
        if (weekCurrent.month() !== monthNow) break
      }
    }

    async function renderMonthSelect () {
      const monthSelect = new FramelixFormFieldSelect()
      monthSelect.showResetButton = false
      monthSelect.maxWidth = null

      monthSelect.container.on(FramelixFormField.EVENT_CHANGE_USER, function () {
        monthSelected = FramelixDateUtils.anyToDayJs(monthSelect.getValue())
        renderCalendar()
        renderMonthSelect()
      })

      for (let i = -12; i <= 12; i++) {
        const date = monthSelected.clone().add(i, 'month')
        monthSelect.addOption(FramelixDateUtils.anyToFormat(date, 'YYYY-MM-01'), await FramelixLang.get('__framelix_month_' + (date.month() + 1) + '__') + ' ' + date.year()
        )
      }
      monthSelect.defaultValue = FramelixDateUtils.anyToFormat(monthSelected, 'YYYY-MM-01')
      monthSelect.render()

      container.find('.framelix-form-field-date-popup-monthpicker-select').empty().append(monthSelect.container)
    }

    const self = this
    this.datepickerPopup = FramelixPopup.show(this.field, '', { color: 'light', placement: 'bottom', width: null })

    this.datepickerPopup.destroyed.then(function () {
      self.datepickerPopup = null
    })

    let dateSelected = FramelixDateUtils.anyToDayJs(this.getValue()) || dayjs()
    let monthSelected = dateSelected.clone()

    renderMonthSelect()

    const container = $(`<div class="framelix-form-field-date-popup">
        <div class="framelix-form-field-date-popup-monthpicker">
            <framelix-button theme="transparent" icon="704" data-action="month_switch" data-dir="-1"></framelix-button>
            <div class="framelix-form-field-date-popup-monthpicker-select"></div>
            <framelix-button theme="transparent" icon="705" data-action="month_switch" data-dir="1"></framelix-button>
        </div>
        <div class="framelix-form-field-date-popup-calendar"></div>
        <div class="framelix-form-field-date-popup-actions">
            <framelix-button theme="primary" icon="719" data-action="delete">Löschen</framelix-button>
            <framelix-button theme="primary" icon="72a" data-action="today">Heute</framelix-button>
        </div>
    </div>`)

    container.on('click', '[data-date]', function () {
      self.setValue(this.dataset.date, true)
      self.datepickerPopup?.destroy()
      // todo fix
      self.datepickerBtn?.trigger('focus')
    })

    container.on('click', '[data-action]', function () {
      switch (this.dataset.action) {
        case 'delete':
          self.setValue(null, true)
          self.datepickerPopup?.destroy()
          // todo fix
          self.datepickerBtn?.trigger('focus')
          break
        case 'today':
          self.setValue(dayjs(), true)
          self.datepickerPopup?.destroy()
          // todo fix
          self.datepickerBtn?.trigger('focus')
          break
        case 'month_switch':
          const dir = parseInt(this.dataset.dir)
          monthSelected = monthSelected.add(dir, 'month')
          renderMonthSelect()
          renderCalendar()
          break
      }
    })
    this.datepickerPopup.content.empty()
    this.datepickerPopup.content.append(container)
    renderCalendar()

  }

  /**
   * Render the field into the container
   * @return {Promise<void>} Resolved when field is fully functional
   */
  async renderInternal () {
    await super.renderInternal()
    const self = this
    this.input.attr('type', 'text')

    this.input.on('change input', function (ev) {
      if (ev.key === 'Enter' || ev.type === 'change') {
        let val = self.stringifyValue(self.getValue())
        val = val.trim().replace(/[^0-9\.]/ig, '.')
        if (!val.match(/[^0-9]/) && val >= 1) {
          const now = new Date()
          let day = now.getDate()
          let month = now.getMonth() + 1
          let year = now.getFullYear()
          if (val.length <= 2) {
            day = parseInt(val)
          } else if (val.length === 3) {
            day = parseInt(val.substring(0, 1))
            month = parseInt(val.substring(1))
          } else if (val.length === 4) {
            day = parseInt(val.substring(0, 2))
            month = parseInt(val.substring(2))
          } else if (val.length === 6) {
            day = parseInt(val.substring(0, 2))
            month = parseInt(val.substring(2, 4))
            year = parseInt('20' + val.substring(4))
          } else if (val.length === 8) {
            day = parseInt(val.substring(0, 2))
            month = parseInt(val.substring(2, 4))
            year = parseInt(val.substring(4))
          } else {
            val = null
          }
          if (val !== null) {
            let date = dayjs()
            val = date.date(day).month(month - 1).year(year)
          }
        }
        self.setValue(val)
      }
    })

    if (this.showDatepickerBtn) {
      this.datepickerBtn = $(`<framelix-button icon="72a" theme="primary"></framelix-button>`)
      this.field.attr('data-field-with-button', '1')
      this.field.append(this.datepickerBtn)
      this.datepickerBtn.on('click', function () {
        self.showDatepicker()
      })
    }
  }
}

FramelixFormField.classReferences['FramelixFormFieldDate'] = FramelixFormFieldDate