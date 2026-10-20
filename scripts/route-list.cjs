const fs = require('fs')
const path = require('path')

const routerPath = path.resolve(
  __dirname,
  '../src/router/index.js'
)

const content = fs.readFileSync(routerPath, 'utf8')

const routes = [...content.matchAll(
  /path:\s*['"`]([^'"`]+)['"`]/g
)]

console.table(
  routes.map((route, index) => ({
    No: index + 1,
    Path: route[1]
  }))
)