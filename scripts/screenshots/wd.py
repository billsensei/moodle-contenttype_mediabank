"""Tiny WebDriver client for chromedriver on 127.0.0.1:9515 (screenshots of the Behat site on :8010)."""
import base64, json, sys, time, urllib.request

DRIVER = 'http://127.0.0.1:9515'
import os
SITE = os.environ.get('MEDIABANK_SHOT_SITE', 'http://127.0.0.1:8010')


def call(method, path, body=None):
    req = urllib.request.Request(DRIVER + path, method=method, data=json.dumps(body if body is not None else {}).encode(),
                                 headers={'Content-Type': 'application/json'})
    return json.load(urllib.request.urlopen(req, timeout=120))['value']


class Browser:
    def __init__(self, width=1280, height=900):
        caps = {'capabilities': {'alwaysMatch': {'goog:chromeOptions': {
            'binary': '/usr/bin/chromium',
            'args': ['--headless=new', '--no-sandbox', '--disable-gpu', '--autoplay-policy=no-user-gesture-required',
                     f'--window-size={width},{height}']}}}}
        self.sid = call('POST', '/session', caps)['sessionId']
        call('POST', f'/session/{self.sid}/window/rect', {'width': width, 'height': height})

    def go(self, url):
        call('POST', f'/session/{self.sid}/url', {'url': url if url.startswith('http') else SITE + url})
        time.sleep(1.2)

    def find(self, css):
        return call('POST', f'/session/{self.sid}/element', {'using': 'css selector', 'value': css})

    def eid(self, el):
        return list(el.values())[0]

    def type(self, css, text):
        call('POST', f'/session/{self.sid}/element/{self.eid(self.find(css))}/value', {'text': text})

    def click(self, css):
        call('POST', f'/session/{self.sid}/element/{self.eid(self.find(css))}/click')

    def login(self, user):
        self.go('/login/index.php')
        self.type('#username', user)
        self.type('#password', user)
        self.click('#loginbtn')
        time.sleep(1.5)

    def js(self, script, *args):
        return call('POST', f'/session/{self.sid}/execute/sync', {'script': script, 'args': list(args)})

    def shot(self, out):
        open(out, 'wb').write(base64.b64decode(call('GET', f'/session/{self.sid}/screenshot')))

    def shot_el(self, css, out):
        el = self.eid(self.find(css))
        open(out, 'wb').write(base64.b64decode(call('GET', f'/session/{self.sid}/element/{el}/screenshot')))

    def title(self):
        return call('GET', f'/session/{self.sid}/title')

    def url(self):
        return call('GET', f'/session/{self.sid}/url')

    def close(self):
        call('DELETE', f'/session/{self.sid}')
