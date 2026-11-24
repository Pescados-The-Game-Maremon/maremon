// Teste local sem navegador, sem banco, sem dependências externas.
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
function elemento() {
    return {value:'',disabled:false,hidden:false,textContent:'',children:[],
        addEventListener(){}, replaceChildren(){this.children=[];this.value='';},
        appendChild(opt){this.children.push(opt);if(this.children.length===1)this.value=String(opt.value);}};
}
const elementos = new Map();
const document = {getElementById(id){if(!elementos.has(id))elementos.set(id,elemento());return elementos.get(id);},createRange(){return {createContextualFragment(){return {firstElementChild:elemento()};}};}};
const context = vm.createContext({document,fetch:()=>new Promise(()=>{}),console});
vm.runInContext(fs.readFileSync(__dirname+'/../js/elementos.js','utf8'),context);
vm.runInContext(fs.readFileSync(__dirname+'/../js/inventario.js','utf8'),context);
vm.runInContext(`evolucoes=[{id:7,pescado_origem_id:4,item_necessario_id:8,destino:'Karkinos',tipo:'DRK',quantidade_item:1,ja_possui:0}];
itemSelecionado={id:8,nome:'dna_karkinos',efeito:'dna_boss',quantidade:1};
pescadoSelecionado={id:4,nome:'Caranguejo_Azul',nivel:5};atualizarBotaoUsar();`,context);
assert.equal(elementos.get('botao_usar').disabled,false);
assert.match(elementos.get('detalhe_evolucao').textContent,/Karkinos/);
vm.runInContext('pescadoSelecionado={id:99,nome:"Boto"};atualizarBotaoUsar();',context);
assert.equal(elementos.get('botao_usar').disabled,true);
vm.runInContext('pescadoSelecionado={id:4,nome:"Caranguejo_Azul"};evolucoes[0].ja_possui=1;atualizarBotaoUsar();',context);
assert.equal(elementos.get('botao_usar').disabled,true);
vm.runInContext('evolucoes[0].ja_possui=0;emCombate=true;atualizarBotaoUsar();',context);
assert.equal(elementos.get('botao_usar').disabled,true);
vm.runInContext('emCombate=false;itemSelecionado.quantidade=0;atualizarBotaoUsar();',context);
assert.equal(elementos.get('botao_usar').disabled,true);
console.log('OK: interface de evolução, item incompatível, duplicidade, combate e quantidade.');
