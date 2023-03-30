class FramelixDocs {
  static init () {
    hljs.highlightAll()
    hljs.initLineNumbersOnLoad()
  }

  static async codeBlockAction (el, action, filename, fileContents) {
    const contents = await (await fetch('data:text/plain;base64,' + $(el).closest('.code-block').attr('data-originalcode'))).blob()
    if (action === 'clipboard') {
      navigator.clipboard.writeText(await contents.text()).then(function () {
        FramelixToast.success('Text copied to clipboard')
      }, function (err) {
      })
    }
    if (action === 'download') {
      Framelix.downloadBlobAsFile(contents, filename)
    }
  }
}

FramelixInit.late.push(FramelixDocs.init)