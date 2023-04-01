class FramelixDocs {
  static codeBlockMap = new Map()

  static init () {
    $('.code-block').each(function () {
      const contents = JSON.parse($(this).next().html())
      FramelixDocs.codeBlockMap.set(this, contents)
      $(this).find('code')[0].innerHTML = contents.replace(/</g, '&lt;').replace(/>/g, '&gt;')
    })
    hljs.highlightAll()
    hljs.initLineNumbersOnLoad()
  }

  static async codeBlockAction (el, action, filename, fileContents) {
    const codeBlock = $(el).closest('.code-block')
    const contents = FramelixDocs.codeBlockMap.get(codeBlock[0])
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

FramelixInit.late.push(FramelixDocs.init)