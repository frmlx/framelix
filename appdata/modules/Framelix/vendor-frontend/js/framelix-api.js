/**
 * Api request utils to comminucate with the build in API
 */
class FramelixApi {

  /**
   * Default url parameters to always append
   * Helpful to set a global context for the api
   * @type {{}|null}
   */
  static defaultUrlParams = null

  /**
   * Do a request and return the json result
   * @param {string} requestType post|get|put|delete
   * @param {string} method The api method
   * @param {Object=} urlParams Url parameters
   * @param {Object=} data Body data to submit
   * @return {Promise<*>}
   */
  static async request (requestType, method, urlParams, data) {
    if (FramelixApi.defaultUrlParams) {
      urlParams = Object.assign({}, FramelixApi.defaultUrlParams, urlParams || {})
    }
    const request = FramelixRequest.request(requestType, FramelixConfig.applicationUrl + '/api/' + method, urlParams, data ? JSON.stringify(data) : null)
    return new Promise(async function (resolve) {
      if (await request.checkHeaders() === 0) {
        return resolve(await request.getJson())
      }
    })
  }
}