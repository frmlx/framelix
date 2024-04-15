const fs = require('fs')
const path = require('path')
const babelCore = require('@babel/core')
const sass = require('sass')

const cmdParams = JSON.parse((Buffer.from(process.argv[2], 'base64').toString('utf8')))

let fileDataCombined = ''
if (cmdParams.type === 'js' && cmdParams.options.jsStrict && cmdParams.options.compile && cmdParams.files.length) {
  fileDataCombined += '\'use strict\';\n\n'
}
for (let i = 0; i < cmdParams.files.length; i++) {
  let fileData = fs.readFileSync(cmdParams.files[i]).toString()
  if (cmdParams.type === 'js') {
    // remove sourcemapping, we don't want that
    fileData = fileData.replace(/^\/\/# sourceMappingURL=.*$/im, '')
    // remove use strict from seperate files because it's added at the top
    fileData = fileData.replace(/^'use strict'|^\"use strict\"/im, '')
    // add semicolons as they will be sometimes required when they are missing the source
    fileData = ';' + fileData + ';\n\n'
  } else {
    fileData += '\n'
  }
  fileDataCombined += fileData
}

if (cmdParams.options.compile && cmdParams.files.length) {
  if (cmdParams.type === 'js') {
    fileDataCombined = babelCore.transform(fileDataCombined, {
      'comments': false,
      'presets': [[__dirname + '/../node_modules/@babel/preset-env', {
        'targets': {
          'chrome': '77',
          'firefox': '98',
          'safari': '15'
        }
      }]],
    }).code
  } else {
    fileDataCombined = sass.compileString(fileDataCombined).css.toString()
  }
}
const dir = path.dirname(cmdParams.distFilePath)
if (!fs.existsSync(dir)) {
  fs.mkdirSync(dir)
}
fs.writeFileSync(cmdParams.distFilePath, fileDataCombined)
console.log(cmdParams.distFilePath + ' successfully compiled')