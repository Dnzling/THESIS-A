const fs = require('fs');
const path = 'resources/js/Pages/System/Procurement/PurchaseRequisitions/PurchaseRequisitionDetail.vue';
const s = fs.readFileSync(path, 'utf8');
const m = s.match(/<template[^>]*>([\s\S]*?)<\/template>/);
if (!m) { console.log('no template found'); process.exit(2); }
const t = m[1];
const stack = [];
const re = /<\/?([a-zA-Z0-9_:-]+)/g;
let match;
const selfClosingSingles = new Set(['br','img','input','hr','meta','link','area','base','col','embed','param','source','track','wbr']);
while ((match = re.exec(t))) {
  const full = match[0];
  const tag = match[1];
  const isClose = full.startsWith('</');
  const ahead = t.slice(match.index, match.index + 200);
  const selfClose = /<[^>]+\/>/.test(ahead) || selfClosingSingles.has(tag.toLowerCase());
  if (isClose) {
    if (stack.length === 0 || stack[stack.length-1] !== tag) {
      const before = t.slice(0, match.index);
      const line = before.split('\n').length;
      const contextStart = Math.max(0, match.index - 120);
      const context = t.slice(contextStart, match.index + 120);
      console.log('Mismatch closing tag:', tag, 'expected:', stack[stack.length-1] || 'none', 'at index', match.index, 'line', line);
      console.log('Context:\n', context);
      process.exit(1);
    }
    stack.pop();
  } else if (!selfClose) {
    stack.push(tag);
  }
}
if (stack.length) {
  console.log('Unclosed tags:', stack.join(', '));
  process.exit(1);
}
console.log('Template tags OK');
