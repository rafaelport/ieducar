<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	*																	     *
	*	@author Prefeitura Municipal de Itaja�								 *
	*	@updated 29/03/2007													 *
	*   Pacote: i-PLB Software P�blico Livre e Brasileiro					 *
	*																		 *
	*	Copyright (C) 2006	PMI - Prefeitura Municipal de Itaja�			 *
	*						ctima@itajai.sc.gov.br					    	 *
	*																		 *
	*	Este  programa  �  software livre, voc� pode redistribu�-lo e/ou	 *
	*	modific�-lo sob os termos da Licen�a P�blica Geral GNU, conforme	 *
	*	publicada pela Free  Software  Foundation,  tanto  a vers�o 2 da	 *
	*	Licen�a   como  (a  seu  crit�rio)  qualquer  vers�o  mais  nova.	 *
	*																		 *
	*	Este programa  � distribu�do na expectativa de ser �til, mas SEM	 *
	*	QUALQUER GARANTIA. Sem mesmo a garantia impl�cita de COMERCIALI-	 *
	*	ZA��O  ou  de ADEQUA��O A QUALQUER PROP�SITO EM PARTICULAR. Con-	 *
	*	sulte  a  Licen�a  P�blica  Geral  GNU para obter mais detalhes.	 *
	*																		 *
	*	Voc�  deve  ter  recebido uma c�pia da Licen�a P�blica Geral GNU	 *
	*	junto  com  este  programa. Se n�o, escreva para a Free Software	 *
	*	Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA	 *
	*	02111-1307, USA.													 *
	*																		 *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
/**
 * @author Adriano Erik Weiguert Nagasava
 */
require_once ("include/clsBase.inc.php");
require_once ("include/clsCadastro.inc.php");
require_once ("include/clsBanco.inc.php");
require_once( "include/pmieducar/geral.inc.php" );

class clsIndexBase extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} i-Educar - Cliente" );
		$this->processoAp = "603";
	}
}

class indice extends clsCadastro
{
	/**
	 * Referencia pega da session para o idpes do usuario atual
	 *
	 * @var int
	 */
	var $pessoa_logada;

	var $cod_cliente;
	var $ref_cod_instituicao;
	var $ref_cod_escola;
	var $ref_cod_biblioteca;
	var $ref_cod_biblioteca_atual;
	var $ref_cod_cliente_tipo;
	var $ref_usuario_exc;
	var $ref_usuario_cad;
	var $ref_idpes;
	var $login_;
	var $senha_;
	var $data_cadastro;
	var $data_exclusao;
	var $ativo;
	var $del_cod_cliente;
	var $del_cod_cliente_tipo;
	

	function Inicializar()
	{
		$retorno = "Novo";
		@session_start();
		$this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();

		$this->cod_cliente	 = $_GET["cod_cliente"];
		$this->ref_cod_biblioteca = $_GET["ref_cod_biblioteca"];

		$obj_permissoes = new clsPermissoes();
		$obj_permissoes->permissao_cadastra( 603, $this->pessoa_logada, 11,  "educar_cliente_lst.php" );
		if( is_numeric( $this->cod_cliente ) && is_numeric($this->ref_cod_biblioteca) )
		{
			$obj = new clsPmieducarCliente( $this->cod_cliente );
			$registro  = $obj->detalhe();
			if( $registro )
			{
				foreach( $registro AS $campo => $val )	// passa todos os valores obtidos no registro para atributos do objeto
					$this->$campo = $val;
				$this->data_cadastro = dataFromPgToBr( $this->data_cadastro );
				$this->data_exclusao = dataFromPgToBr( $this->data_exclusao );

				$this->login_ = $this->login;
				$this->senha_ = $this->senha;
				
				$obj_permissoes = new clsPermissoes();
				if( $obj_permissoes->permissao_excluir( 603, $this->pessoa_logada, 11 ) )
				{
					$this->fexcluir = true;
				}

					$retorno = "Editar";
			}
		}
		$this->url_cancelar = ($retorno == "Editar") ? "educar_cliente_det.php?cod_cliente={$registro["cod_cliente"]}&ref_cod_biblioteca={$this->ref_cod_biblioteca}" : "educar_cliente_lst.php";
		$this->nome_url_cancelar = "Cancelar";
		return $retorno;
	}

	function Gerar()
	{
		// primary keys
		$this->campoOculto( "cod_cliente", $this->cod_cliente );
		$this->campoOculto("requisita_senha", "0");
		$opcoes = array( "" => "Pesquise a pessoa clicando na lupa ao lado" );
		if( $this->ref_idpes )
		{
			$objTemp = new clsPessoaFisica( $this->ref_idpes );
			$detalhe = $objTemp->detalhe();
			$opcoes["{$detalhe["idpes"]}"] = $detalhe["nome"];
		}
		if ( !$this->cod_cliente ) {
			$parametros = new clsParametrosPesquisas();
			$parametros->setSubmit( 0 );
			$parametros->adicionaCampoSelect( "ref_idpes", "idpes", "nome" );
			$parametros->setPessoa( 'F' );
			$parametros->setPessoaCPF( 'N' );
			$parametros->setCodSistema( 1 );
			$parametros->setPessoaNovo( 'S' );
			$parametros->setPessoaTela( 'frame' );
      
			$this->campoListaPesq( "ref_idpes", "Cliente", $opcoes, $this->ref_idpes, "pesquisa_pessoa_lst.php", "", false, "", "", null, null, "", false, $parametros->serializaCampos() );
		}
		else {
			$this->campoOculto( "ref_idpes", $this->ref_idpes );
			$this->campoRotulo( "nm_cliente", "Cliente", $detalhe["nome"] );
		}


		// text
		$this->campoNumero( "login", "Login", $this->login_, 9, 9, false );
		$this->campoSenha( "senha", "Senha", $this->senha_, false );

		$get_escola     = 1;
		$get_biblioteca = 1;
		$instituicao_obrigatorio = true;
		$escola_obrigatorio = false;
		$biblioteca_obrigatorio = true;
		include("include/pmieducar/educar_campo_lista.php");

		//$instituicao_obrigatorio  = true;
		//$escola_obrigatorio		  = false;
		//$biblioteca_obrigatorio	  = true;
		$cliente_tipo_obrigatorio = true;
		$get_instituicao 		  = true;
		$get_escola		 		  = true;
		//$get_biblioteca  		  = true;
		$get_cliente_tipo		  = true;

		if($this->cod_cliente && $this->ref_cod_biblioteca)
		{
			//$instituicao_desabilitado = true;
			//$escola_desabilitado = true;
			//$biblioteca_desabilitado = false;

			$db = new clsBanco();
			$this->ref_cod_instituicao  = $db->CampoUnico("SELECT ref_cod_instituicao  FROM pmieducar.biblioteca, pmieducar.cliente_tipo_cliente ctc, pmieducar.cliente_tipo ct WHERE ref_cod_cliente = '$this->cod_cliente' AND ref_cod_cliente_tipo = cod_cliente_tipo AND ct.ref_cod_biblioteca = cod_biblioteca AND ctc.ref_cod_biblioteca = {$this->ref_cod_biblioteca}");
			$this->ref_cod_escola       = $db->CampoUnico("SELECT ref_cod_escola  FROM pmieducar.biblioteca, pmieducar.cliente_tipo_cliente ctc, pmieducar.cliente_tipo ct WHERE ref_cod_cliente = '$this->cod_cliente' AND ref_cod_cliente_tipo = cod_cliente_tipo AND ct.ref_cod_biblioteca = cod_biblioteca AND ctc.ref_cod_biblioteca = {$this->ref_cod_biblioteca}");
			$this->ref_cod_biblioteca   = $db->CampoUnico("SELECT cod_biblioteca  FROM pmieducar.biblioteca, pmieducar.cliente_tipo_cliente ctc, pmieducar.cliente_tipo ct WHERE ref_cod_cliente = '$this->cod_cliente' AND ref_cod_cliente_tipo = cod_cliente_tipo AND ct.ref_cod_biblioteca = cod_biblioteca AND ctc.ref_cod_biblioteca = {$this->ref_cod_biblioteca}");
			$this->ref_cod_cliente_tipo = $db->CampoUnico("SELECT ref_cod_cliente_tipo FROM pmieducar.cliente_tipo_cliente WHERE ref_cod_cliente = '$this->cod_cliente'");// AND ref_cod_cliente_tipo IN (SELECT cod_cliente_tipo FROM pmieducar.cliente_tipo WHERE ref_cod_biblioteca = )");//IN (SELECT ref_cod_biblioteca FROM pmieducar.biblioteca_usuario WHERE ref_cod_usuario = '$this->pessoa_logada'))");
			$this->ref_cod_biblioteca_atual = $this->ref_cod_biblioteca;
			$this->campoOculto("ref_cod_biblioteca_atual", $this->ref_cod_biblioteca_atual);
		}
		include( "include/pmieducar/educar_campo_lista.php" );
	}

	function Novo()
	{
		@session_start();
		 $this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();
		$senha = md5( $this->senha_."asnk@#*&(23" );
		$obj_permissoes = new clsPermissoes();
		$obj_permissoes->permissao_cadastra( 603, $this->pessoa_logada, 11,  "educar_cliente_lst.php" );
		$obj 	 = new clsPmieducarCliente( null, null, null, $this->ref_idpes );
		$detalhe = $obj->detalhe();
		if ( !$detalhe )
		{
			$obj_cliente = new clsPmieducarCliente();
			$lst_cliente = $obj_cliente->lista( null, null, null, null, $this->login_ );
			if ( $lst_cliente && $this->login_ != "") {
				$this->mensagem = "Este login j� est� sendo utilizado por outra pessoa!<br>";
				echo "<!--\nErro ao cadastrar clsPmieducarCliente\nvalores obrigatorios\nis_numeric( $this->ref_cod_cliente_tipo ) && is_numeric( $this->ref_usuario_cad ) && is_numeric( $this->ref_idpes ) && is_numeric( $this->login_ )\n-->";
			}
			else {
				$obj = new clsPmieducarCliente( $this->cod_cliente, null, $this->pessoa_logada, $this->ref_idpes, $this->login_, $senha, $this->data_cadastro, $this->data_exclusao, 1 );
				$cadastrou = $obj->cadastra();
				if( $cadastrou )
				{
					$this->cod_cliente  = $cadastrou;
					$obj_cliente_tipo = new clsPmieducarClienteTipoCliente( $this->ref_cod_cliente_tipo, $this->cod_cliente, null, null, $this->pessoa_logada, $this->pessoa_logada, 1 );
					if ( $obj_cliente_tipo->existeCliente() )
					{
						if ( $obj_cliente_tipo->trocaTipo() )
						{
							$this->mensagem .= "Cadastro efetuado com sucesso.<br>";
							header( "Location: educar_definir_cliente_tipo_lst.php" );
							die();
							return true;
						}
					}
					else
					{
						$obj_cliente_tipo = new clsPmieducarClienteTipoCliente( $this->ref_cod_cliente_tipo, $this->cod_cliente, null, null, $this->pessoa_logada, null, 1 );
						if ( $obj_cliente_tipo->cadastra() )
						{
							$this->mensagem .= "Cadastro efetuado com sucesso.<br>";
							header( "Location: educar_cliente_lst.php" );
							die();
							return true;
						}

					}

				}

				$this->mensagem = "Cadastro n&atilde;o realizado.<br>";
				echo "<!--\nErro ao cadastrar clsPmieducarCliente\nvalores obrigatorios\nis_numeric( $this->ref_cod_cliente_tipo ) && is_numeric( $this->pessoa_logada ) && is_numeric( $this->ref_idpes ) && is_numeric( $this->login_ )\n-->";
				return false;
			}
		}
		else {
			$obj = new clsPmieducarCliente( null, null, null, $this->ref_idpes );
			$registro  = $obj->detalhe();

			if( $registro )
				$this->cod_cliente = $registro["cod_cliente"];
			$this->ativo = 1;

			$sql = "SELECT COUNT(0) FROM pmieducar.cliente_tipo_cliente WHERE ref_cod_cliente_tipo = {$this->cod_cliente}
					AND ref_cod_biblioteca = {$this->ref_cod_biblioteca} AND ativo = 1";
			$db = new clsBanco();
			$possui_biblio = $db->CampoUnico($sql);
			if ($possui_biblio == 0)
			{
				$obj_cliente_tipo_cliente = new clsPmieducarClienteTipoCliente($this->ref_cod_cliente_tipo, $this->cod_cliente, null, null, $this->pessoa_logada, null, null, $this->ref_cod_biblioteca);
				if (!$obj_cliente_tipo_cliente->cadastra())
				{
					$this->mensagem = "N�o cadastrou";
					return false;
				}
				else 
				{
					header( "Location: educar_cliente_lst.php" );
					return true;
					die();
				}
			}
			else 
			{
				$this->Editar();
				$this->mensagem = "O cliente j� est� cadastrado!<br>";
				echo "<!--\nErro ao cadastrar clsPmieducarCliente\nvalores obrigatorios\nis_numeric( $this->ref_cod_cliente_tipo ) && is_numeric( $this->ref_usuario_cad ) && is_numeric( $this->ref_idpes ) && is_numeric( $this->login_ )\n-->";
			}
		}
	}

	function Editar()
	{
		@session_start();
		 $this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();

		$senha = md5( $this->senha_."asnk@#*&(23" );
		$obj_permissoes = new clsPermissoes();
		$obj_permissoes->permissao_cadastra( 603, $this->pessoa_logada, 11,  "educar_cliente_lst.php" );

		$obj = new clsPmieducarCliente( $this->cod_cliente, $this->pessoa_logada, $this->pessoa_logada, $this->ref_idpes, $this->login_, $senha, $this->data_cadastro, $this->data_exclusao, $this->ativo );
		$editou = $obj->edita();
		if( $editou )
		{
			$obj_cliente_tipo = new clsPmieducarClienteTipoCliente( $this->ref_cod_cliente_tipo, $this->cod_cliente, null, null, $this->pessoa_logada, $this->pessoa_logada, 1, $this->ref_cod_biblioteca );
			if ( $obj_cliente_tipo->existeClienteBiblioteca($_POST['ref_cod_biblioteca_atual']) )
			{
				if ( $obj_cliente_tipo->trocaTipoBiblioteca($_POST['ref_cod_biblioteca_atual']) )
				{
					$this->mensagem .= "Cadastro efetuado com sucesso.<br>";
					header( "Location: educar_cliente_lst.php" );
					die();
					return true;
				}
			}
			else
			{
				$obj_cliente_tipo = new clsPmieducarClienteTipoCliente( $this->ref_cod_cliente_tipo, $this->cod_cliente, null, null, $this->pessoa_logada, null, 1, $this->ref_cod_biblioteca );
				if ( $obj_cliente_tipo->cadastra() )
				{
					$this->mensagem .= "Edi&ccedil;&atilde;o efetuada com sucesso.<br>";
					header( "Location: educar_cliente_lst.php" );
					die();
					return true;
				}
			}
		}

		$this->mensagem = "Edi&ccedil;&atilde;o n&atilde;o realizada.<br>";
		echo "<!--\nErro ao editar clsPmieducarCliente\nvalores obrigatorios\nif( is_numeric( $this->cod_cliente ) && is_numeric( $this->ref_usuario_exc ) )\n-->";
		die();
		return false;
	}

	function Excluir()
	{
		@session_start();
		 $this->pessoa_logada = $_SESSION['id_pessoa'];
		@session_write_close();

		$obj_permissoes = new clsPermissoes();
		$obj_permissoes->permissao_excluir( 603, $this->pessoa_logada, 11,  "educar_cliente_lst.php" );

		$obj = new clsPmieducarCliente( $this->cod_cliente, $this->pessoa_logada, null, $this->ref_idpes, null, null, null, null, 0 );
		$excluiu = $obj->excluir();
		if( $excluiu )
		{
			$this->mensagem .= "Exclus&atilde;o efetuada com sucesso.<br>";
			header( "Location: educar_cliente_lst.php" );
			die();
			return true;
		}

		$this->mensagem = "Exclus&atilde;o n&atilde;o realizada.<br>";
		echo "<!--\nErro ao excluir clsPmieducarCliente\nvalores obrigatorios\nif( is_numeric( $this->cod_cliente ) && is_numeric( $this->ref_usuario_exc ) )\n-->";
		return false;
	}
}

// cria uma extensao da classe base
$pagina = new clsIndexBase();
// cria o conteudo
$miolo = new indice();
// adiciona o conteudo na clsBase
$pagina->addForm( $miolo );
// gera o html
$pagina->MakeAll();
?>
<script>
/*
if(document.getElementById('ref_cod_biblioteca').type == 'select-one')
{
	var campoTipo = document.getElementById('ref_cod_cliente_tipo');
	campoTipo.length = 1;
	campoTipo.options[0].text = 'Selecione uma biblioteca';
	campoTipo.disabled = true;
}*/

function getClienteTipo(xml_cliente_tipo)
{
	var campoTipo = document.getElementById('ref_cod_cliente_tipo');
	var DOM_array = xml_cliente_tipo.getElementsByTagName( "cliente_tipo" );

	if(DOM_array.length)
	{
		campoTipo.length = 1;
		campoTipo.options[0].text = 'Selecione um tipo';
		campoTipo.disabled = false;

		for( var i = 0; i < DOM_array.length; i++ )
		{
			campoTipo.options[campoTipo.options.length] = new Option( DOM_array[i].firstChild.data, DOM_array[i].getAttribute("cod_cliente_tipo"),false,false);
		}
	}
	else
		campoTipo.options[0].text = 'A biblioteca n�o possui tipos';
}

document.getElementById('ref_cod_biblioteca').onchange = function()
{
	ajaxBiblioteca();
};

if(document.getElementById('ref_cod_biblioteca').value != '')
{
	ajaxBiblioteca();
}
function ajaxBiblioteca()
{
	var campoBiblioteca = document.getElementById('ref_cod_biblioteca').value;
	var campoTipo = document.getElementById('ref_cod_cliente_tipo');

	campoTipo.length = 1;
	campoTipo.disabled = true;
	campoTipo.options[0].text = 'Carregando situa��o';

	var xml_cliente_tipo = new ajax( getClienteTipo );
	xml_cliente_tipo.envia( "educar_cliente_tipo_xml.php?bib="+campoBiblioteca );

	var xml_biblioteca = new ajax( requisitaSenha );
	xml_biblioteca.envia( "educar_biblioteca_xml.php?bib="+campoBiblioteca );
}

setVisibility('tr_login_', false);
setVisibility('tr_senha_', false);

function requisitaSenha(xml)
{
	var DOM_array = xml.getElementsByTagName( "biblioteca" );
	var campoBiblioteca = document.getElementById('ref_cod_biblioteca').value;

	if (campoBiblioteca == '')
	{
		setVisibility('tr_login_', false);
		setVisibility('tr_senha_', false);
	}
	else
	{
		for( var i = 0; i < DOM_array.length; i++ )
		{
			if (DOM_array[i].getAttribute("requisita_senha") == 0)
			{
				setVisibility('tr_login_', false);
				setVisibility('tr_senha_', false);
				document.getElementById('login_').setAttribute('class', 'geral');
				document.getElementById('senha_').setAttribute('class', 'geral');
				document.getElementById('requisita_senha').value = '0';
			}
			else if (DOM_array[i].getAttribute("requisita_senha") == 1)
			{
				setVisibility('tr_login_', true);
				setVisibility('tr_senha_', true);
				document.getElementById('login_').setAttribute('class', 'obrigatorio');
				document.getElementById('senha_').setAttribute('class', 'obrigatorio');
				document.getElementById('requisita_senha').value = '1';
			}
		}
	}
}
</script>