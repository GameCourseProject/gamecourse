import {Search} from "./reduce";

describe('Reduce', () => {

  describe('Search', () => {
    const parameters = [
      {
        description: 'should find a match w/ first word',
        target: 'Multimedia Content Production',
        query: 'multimedia',
        output: true
      },
      {
        description: 'should find a match w/ incomplete first word',
        target: 'Multimedia Content Production',
        query: 'mul',
        output: true
      },
      {
        description: 'should find a match w/ middle word',
        target: 'Multimedia Content Production',
        query: 'content',
        output: true
      },
      {
        description: 'should find a match w/ incomplete middle word',
        target: 'Multimedia Content Production',
        query: 'cont',
        output: true
      },
      {
        description: 'should find a match w/ sentence',
        target: 'Multimedia Content Production',
        query: 'multimedia content',
        output: true
      },
      {
        description: 'should find a match w/ incomplete sentence',
        target: 'Multimedia Content Production',
        query: 'multimedia cont',
        output: true
      },
      {
        description: 'should find a match w/ copy of query',
        target: 'Multimedia Content Production',
        query: 'Multimedia Content Production',
        output: true
      },
      {
        description: 'should find a match w/ lowercase version',
        target: 'Multimedia Content Production',
        query: 'multimedia content production',
        output: true
      },
      {
        description: 'should find a match w/ uppercase version',
        target: 'Multimedia Content Production',
        query: 'MULTIMEDIA CONTENT PRODUCTION',
        output: true
      },
      {
        description: 'should find a match if empty query',
        target: 'Multimedia Content Production',
        query: '',
        output: true
      },
      {
        description: 'should find a match if query contains only ignored chars',
        target: 'Multimedia Content Production',
        query: ' -.;:\/\'|!\"+_',
        output: true
      },
      {
        description: 'should find a match w/ Portuguese chars',
        target: 'Produção de Conteúdos Multimédia',
        query: 'producao de conteudos multimedia',
        output: true
      },
      {
        description: 'should find a match w/ Norwegian chars',
        target: 'Bjørn Håvard',
        query: 'bjorn havard',
        output: true
      },
    ];

    parameters.forEach(parameter => {
      it(parameter.description, () => {
        expect(Search.search(parameter.target, parameter.query)).toBe(parameter.output);
      });
    });
  });

  // TODO: tests on filtering

})
