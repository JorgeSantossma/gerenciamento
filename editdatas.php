<div id="editarDatasModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fecharEditarDatasModal()">&times;</span>
        <h2>Editar Datas do Produto</h2>
        <form id="editarDatasForm" action="salvar_datas.php" method="POST">
            <input type="hidden" id="cliente_id" name="cliente_id" value="">
            <input type="hidden" id="produto_id" name="produto_id" value="">

            <label>Data de Programação: <input type="date" id="data_programacao" name="data_programacao"></label>
            <label>Data de PCP: <input type="date" id="data_pcp" name="data_pcp"></label>
            <label>Data de Produção: <input type="date" id="data_producao" name="data_producao"></label>

            <button type="submit">Salvar</button>
        </form>
    </div>
</div>