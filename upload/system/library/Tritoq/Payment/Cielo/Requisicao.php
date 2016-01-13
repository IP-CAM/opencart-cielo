<?php

namespace Tritoq\Payment\Cielo;


use Tritoq\Payment\Exception\InvalidArgumentException;
use Tritoq\Payment\Exception\ResourceNotFoundException;
use Encoding;

/**
 *
 * Representa��o de uma requisi��o/chamada de URL
 *
 * Ela � respons�vel por enviar e buscar as informa��es no webservice da Cielo
 *
 *
 * Class Requisicao
 *
 * @category  Library
 * @copyright Artur Magalh�es <nezkal@gmail.com>
 * @package   Tritoq\Payment\Cielo
 * @license   GPL-3.0+
 */
class Requisicao
{
    /**
     *
     * URL de chamada
     *
     * @var string
     */
    private $url;

    /**
     *
     * Objeto XML de Requisi��o
     *
     * @var \SimpleXMLElement
     */
    private $xmlRequisicao;

    /**
     *
     * Objeto XML de Retorno/Resposta
     *
     * @var \SimpleXMLElement
     */
    private $xmlRetorno;

    /**
     *
     * Valor de retorno em texto
     *
     * @var string
     */
    private $retorno;

    /**
     *
     * Status da resposta
     *
     * @var int
     */
    private $status = 200;

    /**
     *
     * Armazena mensagens de erros
     *
     * @var array
     */
    private $errors = array();

    /**
     * @var array
     */
    private $info = array();

    /**
     *
     * Vers�o SSL da conex�o
     *
     * @var integer
     */
    private $sslVersion = 4;

    function __construct($options = null)
    {
        if (is_array($options)) {

            if (isset($options['sslVersion']) && is_int($options['sslVersion'])) {
                $this->sslVersion = $options['sslVersion'];
            }
        }
    }


    /**
     *
     * Retorna se a requisi��o conteve algum erro
     *
     * @return bool
     */
    public function containsError()
    {
        return sizeof($this->errors) > 0;
    }

    /**
     *
     * Retorna os erros ocorridos na requisi��o
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     *
     * Retorna informa��es da Requisi��o
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     *
     * Retorna o valor de retorno
     *
     * @return string
     */
    public function getRetorno()
    {
        return $this->retorno;
    }

    /**
     *
     * Retorna em XML a resposta da requisi��o
     *
     * @return \SimpleXMLElement
     */
    public function getXmlRetorno()
    {
        return $this->xmlRetorno;
    }

    /**
     *
     * Retorna o status da resposta
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     *
     * Seta a URL que ser� chamada
     *
     * @param string $url
     *
     * @throws \Tritoq\Payment\Exception\InvalidArgumentException
     * @return $this
     */
    public function setUrl($url)
    {
        $valida = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);

        if ($valida == false) {
            throw new InvalidArgumentException('URL de retorno inv�lida.');
        }

        $this->url = $url;
        return $this;
    }

    /**
     *
     * Retorna a URL de chamada
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     *
     * Seta o XML de requisi��o
     *
     * @param \SimpleXMLElement $xmlRequisicao
     *
     * @return $this
     */
    public function setXmlRequisicao(\SimpleXMLElement $xmlRequisicao)
    {
        $this->xmlRequisicao = $xmlRequisicao;
        return $this;
    }

    /**
     *
     * Retorna o XML de Requisi��o
     *
     * @return \SimpleXMLElement
     */
    public function getXmlRequisicao()
    {
        return $this->xmlRequisicao;
    }

    /**
     *
     * Seta o XML de Retorno
     *
     * @param \SimpleXMLElement $xmlRetorno
     *
     * @return $this
     */
    public function setXmlRetorno($xmlRetorno)
    {
        $this->xmlRetorno = $xmlRetorno;
        return $this;
    }

    /**
     *
     * Met�do de chamada da requisi��o
     *
     * Feita em curl
     *
     * @param bool $ssl
     *
     * @throws \Tritoq\Payment\Exception\ResourceNotFoundException
     * @throws \Exception
     * @return $this
     */
    public function send($ssl = false)
    {
        if (!$this->xmlRequisicao instanceof \SimpleXMLElement) {
            throw new ResourceNotFoundException('XML de requisi��o est� vazio');
        }

        $xml = Encoding::toISO8859($this->xmlRequisicao->asXML());

        @file_put_contents(DIR_LOGS . 'cielo.log', "\r\n====================================\r\n" . $xml, FILE_APPEND);

        // Iniciando o objeto Curl
        $_curl = curl_init();

        // Retornar a transfer�ncia ao objeto
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, 1);

        // Sempre utilizar uma nova conex�o
        curl_setopt($_curl, CURLOPT_FRESH_CONNECT, 1);

        // Retornar Header
        curl_setopt($_curl, CURLOPT_HEADER, 0);

        // Modo verboso
        curl_setopt($_curl, CURLOPT_VERBOSE, 0);

        // Mostrar o corpo da requisi��o
        curl_setopt($_curl, CURLOPT_NOBODY, 0);

        // Abrindo a url
        curl_setopt($_curl, CURLOPT_URL, $this->url);

        // Habilitando o m�todo POST
        curl_setopt($_curl, CURLOPT_POST, true);

        // envio os campos
        curl_setopt($_curl, CURLOPT_POSTFIELDS, "mensagem={$xml}");

        //  o tempo em segundos de espera para obter uma conex�o
        curl_setopt($_curl, CURLOPT_CONNECTTIMEOUT, 10);

        //  o tempo m�ximo em segundos de espera para a execu��o da requisi��o (curl_exec)
        curl_setopt($_curl, CURLOPT_TIMEOUT, 40);

        if (is_string($ssl)) {
            // verifica a validade do certificado
            curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, true);

            // verifica se a identidade do servidor bate com aquela informada no certificado
            curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, 2);

            // informa a localiza��o do certificado para verifica��o com o peer
            //curl_setopt($_curl, CURLOPT_CAINFO, $ssl);
            curl_setopt($_curl, CURLOPT_SSLVERSION, $this->sslVersion);
        }

        // Faz a requisi��o HTTP
        $result = curl_exec($_curl);

        @file_put_contents(DIR_LOGS . 'cielo.log', "\r\n====================================\r\n" . $result, FILE_APPEND);

        // Armazenando informa��es da requisi��o

        $info = curl_getinfo($_curl);

        // Fecho a conex�o
        curl_close($_curl);

        // Verificando o status da requisi��o
        $this->status = (integer)(isset($info['http_code']) ? $info['http_code'] : 400);

        // Armazenando as informa�oes
        $this->info = $info;

        // Se o servi�o estiver OK
        if ($this->status != 400) {

            $this->retorno = Encoding::toISO8859(Encoding::fixUTF8($result));

            // tenta armazenar em um XML o resultado
            try {
                $this->xmlRetorno = @simplexml_load_string($this->retorno);

            } catch (\Exception $e) {
                $this->errors[] = $e->getTraceAsString();
            }

            if(!$this->xmlRetorno) {
                $this->xmlRetorno = new \SimpleXMLElement('<erro xmlns="http://ecommerce.cbmp.com.br" id=""><codigo>001</codigo><mensagem><![CDATA[O XML informado n�o � v�lido]]></mensagem></erro>');
            }

            // Se a resposta tiver uma tag de erro
            if (!empty($this->xmlRetorno->mensagem)) {
                $this->errors[] = (string)$this->xmlRetorno->mensagem;
            }

        } else {
            $this->retorno = $result;
            $this->errors[] = $result;
        }

        return $this;
    }
}
