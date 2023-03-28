class FramelixDocs {
  static codeBlockAction (el, action, filename) {
    const contents = $(el).closest('.code-block').find('code').text().trim()
    if (action === 'clipboard') {
      navigator.clipboard.writeText(contents).then(function () {
        FramelixToast.success('Text copied to clipboard')
      }, function (err) {
      })
    }
    if (action === 'download') {
      Framelix.downloadBlobAsFile(new Blob([contents]), filename)
    }
  }
}