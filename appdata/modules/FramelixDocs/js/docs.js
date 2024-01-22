class FramelixDocs {
  static codeBlockMap = new Map()

  static init () {
    FramelixDocs.renderCodeBlocks()
    $(document).on('click', '.run-js-code', FramelixDocs.runJsCode)
    FramelixDom.addChangeListener('docs', function () {
      FramelixDocs.renderCodeBlocks()
    })
    $(document).on('click', '.run-html-code', FramelixDocs.runHtmlCode)
  }

  static runHtmlCode (ev) {
    const codeBlock = $(ev.target).closest('.code-block')
    const contents = FramelixDocs.codeBlockMap.get(codeBlock[0])
    FramelixModal.show({ bodyContent: contents })
  }

  static runJsCode (ev) {
    const codeBlock = $(ev.target).closest('.code-block')
    const contents = FramelixDocs.codeBlockMap.get(codeBlock[0])
    eval('(async function(){' + contents + '})()')
  }

  static renderCodeBlocks () {
    $('.code-block').not('[data-rendered]').each(function () {
      const contents = JSON.parse($(this).next().html())
      FramelixDocs.codeBlockMap.set(this, contents)
      const code = $(this).find('code')
      code[0].innerHTML = contents.replace(/</g, '&lt;').replace(/>/g, '&gt;')
      hljs.highlightElement(code[0])
      hljs.lineNumbersBlock(code[0])
    }).attr('data-rendered', '1')
  }

  static async codeBlockAction (el, action, filename) {
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