"""End-to-end CMS checks against isolated temporary PHP storage."""
import contextlib, http.cookiejar, json, os, re, shutil, socket, subprocess, tempfile, time, urllib.parse, urllib.request
from pathlib import Path
repo=Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory() as tmp:
    web=Path(tmp)/'public_html';shutil.copytree(repo/'dist',web)
    with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(Path(tmp)/'php.log','w+')
    proc=subprocess.Popen(['php','-S',f'127.0.0.1:{port}','-t',str(web)],stdout=log,stderr=log)
    try:
        base=f'http://127.0.0.1:{port}'
        def client():return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        admin=client()
        def get(c,path='/admin/index.php'):return c.open(base+path).read().decode()
        def post(c,fields,path='/admin/index.php',csrf=True):
            if csrf:fields={'csrf':re.search(r'name="csrf" value="([^"]+)"',get(c,path))[1],**fields}
            return c.open(base+path,urllib.parse.urlencode(fields,doseq=True).encode()).read().decode()
        for _ in range(80):
            try:page=get(admin);break
            except Exception:time.sleep(.1)
        else:raise AssertionError('PHP server did not start')
        assert 'Set up your studio' in page,page
        data=Path(tmp)/'veda-cms-data'
        token=(data/'setup-token.txt').read_text()
        pw='Testing-only-password-5827!'
        assert 'Incorrect setup token' in post(admin,{'action':'setup','token':'wrong','username':'owner','password':pw})
        post(admin,{'action':'setup','token':token,'username':'owner','password':pw})
        assert not (data/'setup-token.txt').exists()
        page=post(admin,{'action':'login','username':'owner','password':pw})
        assert 'Make it your own.' in page
        schema=json.loads((web/'admin/schema.json').read_text())
        key=next(k for k,f in schema['fields'].items() if f['group']=='g1' and f['type']=='text')
        def state():return json.loads((data/'state.json').read_text())
        old=get(admin,'/index.php')
        text='A calm <script>alert(1)</script> & welcome'
        path='/admin/index.php?page=content&group=g1'
        rev=state()['revision']
        post(admin,{'action':'draft','revision':rev,f'fields[{key}]':text},path)
        assert text not in get(admin,'/index.php')
        preview=get(admin,'/index.php?preview=1');assert '&lt;script&gt;' in preview
        assert 'Another user saved changes' in post(admin,{'action':'draft','revision':rev,f'fields[{key}]':'stale'},path)
        post(admin,{'action':'publish','revision':state()['revision'],f'fields[{key}]':text},path)
        assert '&lt;script&gt;' in get(admin,'/index.php')
        post(admin,{'action':'user','username':'editor','password':pw,'role':'editor','active':'on','groups[]':['g1']},'/admin/index.php?page=users')
        editor=client();post(editor,{'action':'login','username':'editor','password':pw})
        assert 'You do not have permission' in post(editor,{'action':'publish','revision':state()['revision'],'fields[f1]':'bad'},'/admin/index.php?page=content&group=g0')
        assert 'Administrator permission required' in post(editor,{'action':'user','username':'intruder','password':pw,'role':'admin','active':'on'},'/admin/index.php?page=users')
        assert 'Session expired' in post(editor,{'action':'publish','csrf':'bad'},path,csrf=False)
        urlkey=next(k for k,f in schema['fields'].items() if f['group']=='g1' and f['type']=='url')
        assert 'not allowed' in post(editor,{'action':'draft','revision':state()['revision'],f'fields[{urlkey}]':'javascript:alert(1)'},path)
        post(admin,{'action':'user','username':'editor','password':'','role':'editor','groups[]':['g1']},'/admin/index.php?page=users')
        assert 'Welcome back' in get(editor)
        post(admin,{'action':'restore','history':'0'},'/admin/index.php?page=history')
        assert get(admin,'/index.php')==old
        assert not re.search(r'\{\{f\d+\}\}',old)
        assert len(schema['fields'])>200
        print('PASS: setup token, login, escaped rendering, draft/live separation, stale-write protection, section permissions, admin-only user management, CSRF, URL validation, disabled-user session revocation, revision restore, complete field substitution')
    finally:
        proc.terminate();proc.wait();log.seek(0)
        errors=log.read()
        if 'PHP Fatal error' in errors or 'PHP Parse error' in errors:print(errors);raise AssertionError('PHP runtime error')
