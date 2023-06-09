/**
 * Framelix backend stuff
 */
class FramelixBackend {

  /**
   * Initialize the backend
   */
  static initEarly () {
    FramelixDeviceDetection.screenSize.addEventListener('change', FramelixBackend.updateLayoutFlags)
    FramelixDeviceDetection.darkMode.addEventListener('change', FramelixBackend.updateLayoutFlags)
    FramelixBackend.updateLayoutFlags()
  }

  /**
   * Initialize the backend
   */
  static initLate () {
    const html = $('html')
    const sidebar = $('.framelix-sidebar')
    $(document).on('keydown', '.framelix-activate-toggle-handler', function (ev) {
      if (ev.key === 'Enter') {
        $(this).trigger('click')
      }
    })
    $(document).on('click', '.framelix-activate-toggle-handler', function () {
      const firstClass = $(this).parent().attr('class').split(' ')[0]
      $(this).parent().toggleClass(firstClass + '-active')
    })
    $(document).on('click', '.framelix-sidebar-toggle', function () {
      html.attr('data-sidebar-status-force', sidebar.width() <= 20 ? 'opened' : 'closed')
      FramelixBackend.updateLayoutFlags()
    })
    let activeLink = sidebar.find('.framelix-sidebar-link-active')
    if (activeLink.length) {
      FramelixIntersectionObserver.isIntersecting(activeLink).then(function (isIntersecting) {
        if (!isIntersecting) {
          Framelix.scrollTo(activeLink, sidebar.children('.framelix-sidebar-inner'), 100, 0)
        }
      })
    }
    sidebar.find('.framelix-form-field[data-name] input').prop('checked', !!FramelixLocalStorage.get('framelix-darkmode'))
  }

  /**
   * Update layout flags
   */
  static updateLayoutFlags () {
    const html = $('html')
    const status = html.attr('data-sidebar-status-force') || (html.attr('data-screen-size') === 's' || html.attr('data-sidebar-status-initial-hidden') === '1' ? 'closed' : 'opened')
    html.attr('data-sidebar-status', status)
    $('.framelix-sidebar').attr('aria-hidden', status === 'closed' ? 'true' : 'false')
  }
}

FramelixInit.early.push(FramelixBackend.initEarly)
FramelixInit.late.push(FramelixBackend.initLate)