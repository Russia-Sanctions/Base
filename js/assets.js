
const fs = require('fs/promises')
const ASSETS_DIR = __dirname + '/../assets/'

module.exports.getHtml = () => fs.readFile(ASSETS_DIR + 'msg.html')
