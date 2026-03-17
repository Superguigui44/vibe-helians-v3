import { resolve } from 'path'
import { defineConfig } from 'vite'
import fs from 'fs'

export default defineConfig({
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
        cabinet: resolve(__dirname, 'cabinet.html'),
        expropriation: resolve(__dirname, 'expropriation.html'),
        preemption: resolve(__dirname, 'preemption.html'),
        immobilier: resolve(__dirname, 'immobilier.html'),
        construction: resolve(__dirname, 'construction.html'),
        hopitaux: resolve(__dirname, 'hopitaux.html'),
        urbanisme: resolve(__dirname, 'urbanisme.html'),
        baux: resolve(__dirname, 'baux.html'),
        blog: resolve(__dirname, 'blog.html'),
        contact: resolve(__dirname, 'contact.html'),
        mentions: resolve(__dirname, 'mentions.html'),
      },
    },
  },
  server: {
    proxy: {
      '/contact.php': 'http://localhost:8888',
      '/admin': 'http://localhost:8888',
    },
  },
  plugins: [
    {
      name: 'serve-content-json',
      configureServer(server) {
        server.middlewares.use('/content.php', (req, res) => {
          const contentPath = resolve(__dirname, 'content.json')
          if (fs.existsSync(contentPath)) {
            res.setHeader('Content-Type', 'application/json; charset=utf-8')
            res.end(fs.readFileSync(contentPath, 'utf-8'))
          } else {
            res.statusCode = 404
            res.end(JSON.stringify({ error: 'content.json not found' }))
          }
        })
      },
    },
  ],
})
