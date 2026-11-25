// Somente marcação constante do próprio projeto. Dados de API entram por textContent.
function elementoHTML(marcacao) {
    return document.createRange().createContextualFragment(marcacao).firstElementChild;
}

// Padroniza nomes de códigos internos para exibição (sem alterar os dados do banco).
window.nomeMaremon = function nomeMaremon(nome) {
    const original = String(nome ?? '').trim();
    if (!original) return '';
    const chave = original.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    const nomes = {
        peixe_palhaco: 'Peixe-palhaço', anomalocaris: 'Anomalocaris', hallucigenia: 'Hallucigenia',
        trilobita: 'Trilobita', carnifibita: 'Carnifibita', aniquilocaris: 'Aniquilocaris',
        peixe_pescador: 'Peixe-pescador', pescador_de_gente: 'Pescador de gente',
        peixe_palhaco: 'Peixe-palhaço', peixe_bolha: 'Peixe-bolha', peixe_espada: 'Peixe-espada',
        peixe_leao: 'Peixe-leão', peixe_serra: 'Peixe-serra', peixe_lua: 'Peixe-lua',
        peixe_gato: 'Peixe-gato', peixe_voador: 'Peixe-voador', peixe_cirurgiao: 'Peixe-cirurgião',
         peixe_pescador: 'Peixe-pescador', pescador_de_gente: 'Pescador de gente',
        bobo_dos_mares: 'Bobo dos mares', peixe_bolha: 'Peixe-bolha', homem_bolha: 'Homem-bolha',
        caranguejo_azul: 'Caranguejo-azul', tubarao_martelo: 'Tubarão-martelo', tubarao_raposa: 'Tubarão-raposa',
        cavalo_marinho: 'Cavalo-marinho', baleia_azul: 'Baleia-azul', baleia_cachalote: 'Baleia-cachalote',
        truta_arco_iris: 'Truta-arco-íris', carpas_siameses: 'Carpas siamesas', carpas_siamesas: 'Carpas siamesas',
        agua_viva: 'Água-viva', boto_rosa: 'Boto-cor-de-rosa', monstro_do_lago_ness: 'Monstro do Lago Ness',
        bake_kujira: 'Bake-kujira', moby_dick: 'Moby Dick', koi_yin: 'Koi Yin', koi_yang: 'Koi Yang',
        salmao: 'Salmão', jacare: 'Jacaré', poraque: 'Poraquê', narval: 'Narval', arraia: 'Arraia',
        tartaruga: 'Tartaruga', polvo: 'Polvo', lula: 'Lula', orca: 'Orca', pirarucu: 'Pirarucu',
        bagre: 'Bagre', baiacu: 'Baiacu', boto: 'Boto', truta: 'Truta', amargama: 'Amargama',
        trilobita: 'Trilobita', carnifibita: 'Carnifibita', anomalocaris: 'Anomalocaris', aniquilocaris: 'Aniquilocaris',
        hallucigenia: 'Hallucigenia', hallucinado: 'Hallucinado', kelpie: 'Kelpie', karkinos: 'Karkinos',
        cipactli: 'Cipactli', genbu: 'Genbu', namazu: 'Namazu', lusca: 'Lusca', leviata: 'Leviata', leviathan: 'Leviathan',
        pedra_evolutiva: 'Pedra Evolutiva', pedra_de_evolucao: 'Pedra Evolutiva', pedra_evolucao: 'Pedra Evolutiva', pedra_de_evolucao: 'Pedra Evolutiva', pedra_de_evolucao_: 'Pedra Evolutiva', dna_boto_rosa: 'DNA do Boto-cor-de-rosa', dna_kelpie: 'DNA do Kelpie',
        dna_karkinos: 'DNA do Karkinos', dna_monstro_do_lago_ness: 'DNA do Monstro do Lago Ness',
        dna_cipactli: 'DNA do Cipactli', dna_genbu: 'DNA do Genbu', dna_namazu: 'DNA do Namazu', dna_lusca: 'DNA do Lusca'
    };
    if (nomes[chave]) return nomes[chave];
    const legivel = original.replace(/[_-]+/g, ' ').replace(/^dna\s*/i, 'DNA ');
    return legivel.replace(/\b[a-zá-ú]/gi, letra => letra.toLocaleUpperCase('pt-BR'));
};
