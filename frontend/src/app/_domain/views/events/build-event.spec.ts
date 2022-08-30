import {EventAction} from "./event-action";
import {EventType} from "./event-type";
import {buildEvent} from "./build-event";
import {EventGoToPage} from "./event-go-to-page";
import {EventHideView} from "./event-hide-view";
import {EventShowView} from "./event-show-view";
import {EventToggleView} from "./event-toggle-view";

describe('Build Event', () => {

  const TYPE = EventType.CLICK;

  describe('Build EventGoToPage', () => {
    const ACTION = EventAction.GO_TO_PAGE;
    const PAGE_ID = 10;
    const USER_ID = 1;

    const parameters = [
      {
        description: 'should build a goToPage event w/ only mandatory params',
        type: TYPE,
        eventStr: ACTION + '(' + PAGE_ID + ')',
        outputKeys: ['type', 'action', 'pageId'],
        outputValues: [TYPE, ACTION, PAGE_ID]
      },
      {
        description: 'should build a goToPage event w/ userId',
        type: TYPE,
        eventStr: ACTION + '(' + PAGE_ID + ', ' + USER_ID + ')',
        outputKeys: ['type', 'action', 'pageId', 'userId'],
        outputValues: [TYPE, ACTION, PAGE_ID, USER_ID]
      },
    ];

    parameters.forEach(parameter => {
      it(parameter.description, () => {
        const event = buildEvent(parameter.type, parameter.eventStr) as EventGoToPage;
        expect(event).toBeTruthy();
        for (let i = 0; i < parameter.outputKeys.length; i++) {
          const key: string = parameter.outputKeys[i];
          const value: any = parameter.outputValues[i];

          expect(event[key]).toBe(value);
        }
      });
    });
  })

  describe('Build EventHideView', () => {
    const ACTION = EventAction.HIDE_VIEW;
    const VIEW_ID = 10;

    const parameters = [
      {
        description: 'should build a hideView event',
        type: TYPE,
        eventStr: ACTION + '(' + VIEW_ID + ')',
        outputKeys: ['type', 'action', 'viewId'],
        outputValues: [TYPE, ACTION, VIEW_ID]
      }
    ];

    parameters.forEach(parameter => {
      it(parameter.description, () => {
        const event = buildEvent(parameter.type, parameter.eventStr) as EventHideView;
        expect(event).toBeTruthy();
        for (let i = 0; i < parameter.outputKeys.length; i++) {
          const key: string = parameter.outputKeys[i];
          const value: any = parameter.outputValues[i];

          expect(event[key]).toBe(value);
        }
      });
    });
  })

  describe('Build EventShowView', () => {
    const ACTION = EventAction.SHOW_VIEW;
    const VIEW_ID = 10;

    const parameters = [
      {
        description: 'should build a showView event',
        type: TYPE,
        eventStr: ACTION + '(' + VIEW_ID + ')',
        outputKeys: ['type', 'action', 'viewId'],
        outputValues: [TYPE, ACTION, VIEW_ID]
      }
    ];

    parameters.forEach(parameter => {
      it(parameter.description, () => {
        const event = buildEvent(parameter.type, parameter.eventStr) as EventShowView;
        expect(event).toBeTruthy();
        for (let i = 0; i < parameter.outputKeys.length; i++) {
          const key: string = parameter.outputKeys[i];
          const value: any = parameter.outputValues[i];

          expect(event[key]).toBe(value);
        }
      });
    });
  })

  describe('Build EventToggleView', () => {
    const ACTION = EventAction.TOGGLE_VIEW;
    const VIEW_ID = 10;

    const parameters = [
      {
        description: 'should build a toggleView event',
        type: TYPE,
        eventStr: ACTION + '(' + VIEW_ID + ')',
        outputKeys: ['type', 'action', 'viewId'],
        outputValues: [TYPE, ACTION, VIEW_ID]
      }
    ];

    parameters.forEach(parameter => {
      it(parameter.description, () => {
        const event = buildEvent(parameter.type, parameter.eventStr) as EventToggleView;
        expect(event).toBeTruthy();
        for (let i = 0; i < parameter.outputKeys.length; i++) {
          const key: string = parameter.outputKeys[i];
          const value: any = parameter.outputValues[i];

          expect(event[key]).toBe(value);
        }
      });
    });
  })

})
