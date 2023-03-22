/**
 * A fields visibility condition
 */
class FramelixFormFieldVisibilityCondition {
  /**
   * The condition data
   * @type {Array}
   */
  data = []

  /**
   * Add an && condition
   * @return {this}
   */
  and () {
    this.data.push({
      'type': 'and'
    })
  }

  /**
   * Add an || condition
   * @return {this}
   */
  or () {
    this.data.push({
      'type': 'or'
    })
    return this
  }

  /**
   * Does the fields value is empty
   * @param {string} fieldName
   * @return {this}
   */
  empty (fieldName) {
    this.data.push({
      'type': 'empty',
      'field': fieldName
    })
    return this
  }

  /**
   * Does the fields value is not empty
   * @param fieldName
   * @return {this}
   */
  notEmpty (fieldName) {
    this.data.push({
      'type': 'notEmpty',
      'field': fieldName
    })
    return this
  }

  /**
   * Does the fields value contains a given value
   * If fieldValue is array than one of the value must match
   * @param {string} fieldName
   * @param {*} value
   * @return {this}
   */
  like (fieldName, value) {
    this.data.push({
      'type': 'like',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Does the fields value not contains a given value
   * If fieldValue is array than all of the value must not match
   * @param {string} fieldName
   * @param {*} value
   * @return {this}
   */
  notLike (fieldName, value) {
    this.data.push({
      'type': 'notLike',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Does the fields value equal given value
   * If fieldValue is array than one of the value must match
   * @param {string} fieldName
   * @param {*} value
   * @return {this}
   */
  equal (fieldName, value) {
    this.data.push({
      'type': 'equal',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Does the fields value not equal given value
   * If fieldValue is array than all of the value must not match
   * @param {string} fieldName
   * @param {*} value
   * @return {this}
   */
  notEqual (fieldName, value) {
    this.data.push({
      'type': 'notEqual',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Does the fields value is greather than
   * If fieldValue is array than it counts the elements in the field value
   * @param {string} fieldName
   * @param {number} value
   * @return {this}
   */
  greatherThan (fieldName, value) {
    this.data.push({
      'type': 'greatherThan',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Does the fields value is greather than equal
   * If fieldValue is array than it counts the elements in the field value
   * @param {string} fieldName
   * @param {number} value
   * @return {this}
   */
  greatherThanEqual (fieldName, value) {
    this.data.push({
      'type': 'greatherThanEqual',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Does the fields value is lower than
   * If fieldValue is array than it counts the elements in the field value
   * @param {string} fieldName
   * @param {number} value
   * @return {this}
   */
  lowerThan (fieldName, value) {
    this.data.push({
      'type': 'lowerThan',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Does the fields value is lower than equal
   * If fieldValue is array than it counts the elements in the field value
   * @param {string} fieldName
   * @param {number} value
   * @return {this}
   */
  lowerThanEqual (fieldName, value) {
    this.data.push({
      'type': 'lowerThanEqual',
      'field': fieldName,
      'value': value
    })
    return this
  }

  /**
   * Clear the condition (unset)
   * @return void
   */
  clear () {
    this.data = []
  }
}