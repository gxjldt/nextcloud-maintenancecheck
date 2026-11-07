#!/usr/bin/env python3
"""Replicate scripts/regenerate-l10n-js.php byte-for-byte (local box has no PHP).
Diffs every generated js against the committed one; exit 1 on any mismatch."""
import json, sys

PLURALS = {
    'en': 'nplurals=2; plural=(n != 1);',
    'de': 'nplurals=2; plural=(n != 1);',
    'fr': 'nplurals=2; plural=(n > 1);',
    'es': 'nplurals=2; plural=(n != 1);',
    'da': 'nplurals=2; plural=(n != 1);',
    'it': 'nplurals=2; plural=(n != 1);',
    'nb': 'nplurals=2; plural=(n != 1);',
    'nl': 'nplurals=2; plural=(n != 1);',
    'pl': 'nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
    'pt': 'nplurals=2; plural=(n != 1);',
    'pt_BR': 'nplurals=2; plural=(n > 1);',
    'sv': 'nplurals=2; plural=(n != 1);',
    'zh_CN': 'nplurals=1; plural=0;',
}

def enc(x):
    # PHP json_encode escapes '/' by default; JSON_UNESCAPED_UNICODE keeps CJK raw
    if isinstance(x, list):
        return json.dumps(x, ensure_ascii=False, separators=(',', ':')).replace('/', '\\/')
    return json.dumps(x, ensure_ascii=False).replace('/', '\\/')

def gen(lang):
    cat = json.load(open(f'l10n/{lang}.json', encoding='utf-8'))
    tr = cat['translations']
    pf = cat.get('pluralForm') or PLURALS[lang]
    if not pf.startswith('nplurals'):
        pf = 'nplurals=2; plural=(' + pf.strip(' \t;') + ');'
    lines = ['OC.L10N.register(\n', '\t"maintenancecheck",\n', '\t{\n']
    items = ['\t' + enc(k) + ' : ' + enc(v) + ',\n' for k, v in tr.items()]
    items[-1] = items[-1].rstrip(',\n') + '\n'
    lines += items + ['\t},\n', '\t' + enc(pf) + '\n', ');\n']
    return ''.join(lines)

langs = [l for l in (sys.argv[1:] or sorted(PLURALS)) if __import__('os').path.isfile(f'l10n/{l}.json')]
missing = [l for l in (sys.argv[1:] or sorted(PLURALS)) if l not in langs]
for m in missing:
    print(f'skip {m} (no json yet)')
fail = 0
for lang in langs:
    out = gen(lang)
    if len(sys.argv) > 1:  # explicit langs = write
        open(f'l10n/{lang}.js', 'w', encoding='utf-8').write(out)
        print(f'wrote l10n/{lang}.js ({len(out)} bytes)')
    else:  # no args = verify all against committed
        committed = open(f'l10n/{lang}.js', encoding='utf-8').read()
        if out == committed:
            print(f'OK   {lang}')
        else:
            fail += 1
            i = next((i for i, (a, b) in enumerate(zip(out, committed)) if a != b), min(len(out), len(committed)))
            print(f'FAIL {lang}: first diff at byte {i}\n  gen: {out[max(0,i-40):i+40]!r}\n  com: {committed[max(0,i-40):i+40]!r}')
sys.exit(1 if fail else 0)
