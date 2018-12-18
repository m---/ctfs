const puppeteer = require('puppeteer');
const mysql = require('mysql');
const crypto = require('crypto');

const DB_HOST = process.env.DB_HOST;
const WEB_HOST = process.env.WEB_HOST;
const USER_KEY_SECRET = process.env.USER_KEY_SECRET;

const sleep = async (time) => {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      resolve()
    }, time)
  })
}

(async () => {
  await sleep(30000)

  const pool = mysql.createPool({
    host: DB_HOST,
    user: 'mage',
    password: 'password',
    database: 'mage'
  })
  
  const visitUrl = async (url) => {
    console.log('[+] start visitUrl: ' + url)
    const browser = await puppeteer.launch({
      args: [
        '--media-cache-size=1',
        '--disk-cache-size=1',
        '--headless',
        '--disable-gpu',
        '--remote-debugging-port=0'
      ]
    })
    const page = await browser.newPage()
    await page.goto(url)
    page.on('error', function (err) {
      console.log('[+] error visitUrl: ' + err.toString()) 
    })
    await sleep(3000)
    await browser.close()
    console.log('[+] end visitUrl: ' + url)
  }
  
  const checkAllReports = async () => {
    return new Promise((resolve, reject) => {
      pool.getConnection((error, connection) => {
        connection.query('SELECT user_id FROM users WHERE reported = 1', (error, results, fields) => {
          connection.query('UPDATE users SET reported = 0', (error, results, fields) => {})
          results.forEach(async (values) => {
            let hmac = crypto.createHmac('sha512', USER_KEY_SECRET)
            let key = hmac.update(values['user_id']).digest('hex')
            await visitUrl('http://' + WEB_HOST + '/admin/log?user_key=' + key)
          })
          connection.release()
          resolve()
        })
      })
    })
  }
  
  console.log('[+] start admin')
  while (true) {
    await checkAllReports()
    await sleep(3000)
  }
})()
