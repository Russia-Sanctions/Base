const fs = require('fs/promises')
const fdPromise = fs.open(__dirname + '/../trees/ipv4.bin')

const ip2long = ip =>
  Buffer.from(ip.split('.').map(val => parseInt(val)))
  .readUInt32BE()

const readNode = p => p.fd.read(p.buf, 0, 12, p.position += 12)
  .then(() => ({
    prefix: p.buf.readUInt32BE(0),
    mask: p.buf.readUInt32BE(4),
    len: p.buf[8] == 0xff ? 0 : p.buf.readUInt32BE(8),
    type: p.buf[8] == 0xff ? p.buf.toString('utf8', 10) : null,
  }))

const doMatch = (p, right) =>
  readNode(p).then(({prefix, mask, len, type}) => {
    if ((p.ip & mask) >>> 0 === prefix) {
      if (type) {
        return type
      } else {
        // Match left
        return doMatch(p)
      }
    } else {
      if (right) {
        return null
      } else {
        p.position += len
        return doMatch(p, true)
      }
    }
  }
)

const matchIp = ip => fdPromise.then(fd => doMatch({
  fd,
  stringIp: ip,
  ip: ip2long(ip),
  buf: new Buffer.alloc(12),
  position: -12
})).then(type => ({ip, type}))

module.exports = matchIp
