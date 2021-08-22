<?php

namespace App\Fakers;

use Faker\Provider\Base;

class FakeCourseNameProvider extends Base
{
    private $names = [
        "Agentes Autónomos e Sistemas Multi-Agente",
        "Algoritmos Avançados",
        "Aplicações e Computação para a Internet das Coisas ",
        "Arquitetura Empresarial",
        "Administração e Gestão de Infraestruturas e Serviços de It ",
        "Ambientes Inteligentes",
        "Algoritmos para Lógica Computacional",
        "Administração de Dados e Sistemas de Informação",
        "Fundamentos de Sistemas de Informação",
        "Aprendizagem",
        "Arquitecturas de Software",
        "Computação em Nuvem e Virtualização",
        "Animação e Visualização Tridimensional",
        "Bioinformática / Biologia Computacional ",
        "Computabilidade e Complexidade",
        "Conceção Centrada no Utilizador",
        "Ciência de Dados",
        "Computação Gráfica para Jogos",
        "Computação Móvel e Ubíqua",
        "Computação Paralela e Distribuída",
        "Criptografia e Protocolos de Segurança",
        "Ciência das Redes Complexas",
        "Ciber Segurança Forense",
        "Comunicação Visual Interactiva",
        "Design de Jogos",
        "Desempenho e Dimensionamento de Redes e Sistemas",
        "Design de Interação para a Internet das Coisas",
        "Engenharia e Tecnologia de Processos de Negócio",
        "Gestão de Projectos Informáticos",
        "Análise e Integração de Dados",
        "Inteligência Artificial para Jogos",
        "Integração Empresarial",
        "Introdução à Robótica",
        "Língua Natural",
        "Linguagens de Programação",
        "Metodologia de Desenvolvimento de Jogos",
        "Gestão de Sistemas de Informação",
        "Programação 3D",
        "Desenvolvimento de Aplicações Distribuídas",
        "Planeamento, Aprendizagem e Decisão Inteligente ",
        "Programação Avançada",
        "Produção de Conteúdos Multimédia",
        "Processamento da Fala",
        "Processamento de Imagem e Visão",
        "Portfolio Pessoal 1",
        "Portfolio Pessoal 2",
        "Procura e Planeamento",
        "Especificação de Software",
        "Representação do Conhecimento e Raciocínio",
        "Processamento e Recuperação de Informação",
        "Robôs Sociais e Interação Pessoa Robô",
        "Realidade Virtual",
        "Sistemas de Elevada Confiabilidade",
        "Segurança Informática em Redes e Sistemas",
        "Sistemas Robóticos em Manipulação",
        "Segurança em Software",
        "Tecnologias de Informação em Saúde/Informática Biomédica",
        "Teste e Validação de Software",
        "Visualização de Informação"
    ];

    public function courseName()
    {
        return $this->generator->randomElement($this->names);
    }
}
