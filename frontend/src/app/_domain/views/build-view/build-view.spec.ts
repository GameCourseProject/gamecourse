import {ViewText, ViewTextDatabase} from "../view-text";
import {buildView} from "./build-view";
import {ViewType} from "../view-type";
import {View, ViewMode} from "../view";
import {EventGoToPage} from "../events/event-go-to-page";
import {EventType} from "../events/event-type";
import {ViewImage, ViewImageDatabase} from "../view-image";
import {ViewHeader, ViewHeaderDatabase} from "../view-header";
import {ViewTable, ViewTableDatabase} from "../view-table";
import {ViewRow, ViewRowDatabase} from "../view-row";
import {ViewBlock, ViewBlockDatabase} from "../view-block";

describe('Build View', () => {

  const ID = '1';
  const ROLE = 'role.Default';
  const STYLE = 'color: red;';
  const CSS_ID = 'some-id';
  const CLASS = 'some-class';
  const VISIBILITY_TYPE = 'visible';
  const EVENTS = '{"click": "goToPage(10)"}';

  const LINK = 'https://tecnico.ulisboa.pt';
  const TEXT = 'This is some text';
  const IMG_SRC = 'photos/image.png';

  // describe('Build View Text', () => {
  //   const TYPE = ViewText.TEXT_CLASS;
  //
  //   const parameters = [
  //     {
  //       description: 'should build a view text w/ only mandatory params',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         value: TEXT
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'value'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.DISPLAY, TEXT]
  //     },
  //     {
  //       description: 'should build a link view text',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         value: TEXT,
  //         link: LINK
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'value', 'link'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.DISPLAY, TEXT, LINK]
  //     },
  //     {
  //       description: 'should build a view text in edit mode',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: true,
  //         value: TEXT
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'value'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.EDIT, TEXT]
  //     },
  //     {
  //       description: 'should build a view text w/ style',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         style: STYLE,
  //         value: TEXT
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'style', 'value'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.DISPLAY, STYLE, TEXT]
  //     },
  //     {
  //       description: 'should build a view text w/ cssId',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         cssId: CSS_ID,
  //         value: TEXT
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'cssId', 'value'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.DISPLAY, CSS_ID, TEXT]
  //     },
  //     {
  //       description: 'should build a view text w/ class',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         class: CLASS,
  //         value: TEXT
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'class', 'value'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.DISPLAY, CLASS + ' ' + View.VIEW_CLASS + ' ' + ViewText.TEXT_CLASS, TEXT]
  //     },
  //     {
  //       description: 'should build a view text w/ visibility type',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         visibilityType: VISIBILITY_TYPE,
  //         value: TEXT
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'visibilityType', 'value'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.DISPLAY, VISIBILITY_TYPE, TEXT]
  //     },
  //     {
  //       description: 'should build a view text w/ events',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         events: EVENTS,
  //         value: TEXT
  //       } as ViewTextDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'events', 'value'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TEXT, 'Default', ViewMode.DISPLAY, {'click': new EventGoToPage(EventType.CLICK, 10)}, TEXT]
  //     },
  //   ];
  //
  //   parameters.forEach(parameter => {
  //     it(parameter.description, () => {
  //       const view = buildView(parameter.obj) as ViewText;
  //       expect(view).toBeTruthy();
  //       for (let i = 0; i < parameter.outputKeys.length; i++) {
  //         const key: string = parameter.outputKeys[i];
  //         const value: any = parameter.outputValues[i];
  //
  //         if (key === 'events') expect(view[key]).toEqual(value);
  //         else expect(view[key]).toBe(value);
  //       }
  //     });
  //   });
  // })
  //
  // describe('Build View Image', () => {
  //   const TYPE = ViewImage.IMAGE_CLASS;
  //
  //   const parameters = [
  //     {
  //       description: 'should build a view image w/ only mandatory params',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         value: IMG_SRC
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'src'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.DISPLAY, IMG_SRC]
  //     },
  //     {
  //       description: 'should build a link view image',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         value: IMG_SRC,
  //         link: LINK
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'src', 'link'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.DISPLAY, IMG_SRC, LINK]
  //     },
  //     {
  //       description: 'should build a view image in edit mode',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: true,
  //         value: IMG_SRC
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'src'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.EDIT, IMG_SRC]
  //     },
  //     {
  //       description: 'should build a view image w/ style',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         style: STYLE,
  //         value: IMG_SRC
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'style', 'src'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.DISPLAY, STYLE, IMG_SRC]
  //     },
  //     {
  //       description: 'should build a view image w/ cssId',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         cssId: CSS_ID,
  //         value: IMG_SRC
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'cssId', 'src'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.DISPLAY, CSS_ID, IMG_SRC]
  //     },
  //     {
  //       description: 'should build a view image w/ class',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         class: CLASS,
  //         value: IMG_SRC
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'class', 'src'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.DISPLAY, CLASS + ' ' + View.VIEW_CLASS + ' ' + ViewImage.IMAGE_CLASS, IMG_SRC]
  //     },
  //     {
  //       description: 'should build a view image w/ visibility type',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         visibilityType: VISIBILITY_TYPE,
  //         value: IMG_SRC
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'visibilityType', 'src'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.DISPLAY, VISIBILITY_TYPE, IMG_SRC]
  //     },
  //     {
  //       description: 'should build a view image w/ events',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         events: EVENTS,
  //         value: IMG_SRC
  //       } as ViewImageDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'events', 'src'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.IMAGE, 'Default', ViewMode.DISPLAY, {'click': new EventGoToPage(EventType.CLICK, 10)}, IMG_SRC]
  //     },
  //   ];
  //
  //   parameters.forEach(parameter => {
  //     it(parameter.description, () => {
  //       const view = buildView(parameter.obj) as ViewImage;
  //       expect(view).toBeTruthy();
  //       for (let i = 0; i < parameter.outputKeys.length; i++) {
  //         const key: string = parameter.outputKeys[i];
  //         const value: any = parameter.outputValues[i];
  //
  //         if (key === 'events') expect(view[key]).toEqual(value);
  //         else expect(view[key]).toBe(value);
  //       }
  //     });
  //   });
  // })
  //
  // describe('Build View Header', () => {
  //   const TYPE = ViewHeader.HEADER_CLASS;
  //   const IMAGE = {
  //     id: (parseInt(ID) + 1).toString(),
  //     viewId: (parseInt(ID) + 1).toString(),
  //     parentId: ID,
  //     partType: ViewImage.IMAGE_CLASS,
  //     role: ROLE,
  //     edit: false,
  //     value: IMG_SRC
  //   } as ViewImageDatabase;
  //   const TITLE = {
  //     id: (parseInt(ID) + 2).toString(),
  //     viewId: (parseInt(ID) + 2).toString(),
  //     parentId: ID,
  //     partType: ViewText.TEXT_CLASS,
  //     role: ROLE,
  //     edit: false,
  //     value: TEXT
  //   } as ViewTextDatabase;
  //
  //   const parameters = [
  //     {
  //       description: 'should build a view header w/ only mandatory params',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         image: IMAGE,
  //         title: TITLE
  //       } as ViewHeaderDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'image', 'title'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.HEADER, 'Default', ViewMode.DISPLAY, buildView(IMAGE), buildView(TITLE)]
  //     },
  //     {
  //       description: 'should build a view header in edit mode',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: true,
  //         image: IMAGE,
  //         title: TITLE
  //       } as ViewHeaderDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'image', 'title'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.HEADER, 'Default', ViewMode.EDIT, buildView(IMAGE), buildView(TITLE)]
  //     },
  //     {
  //       description: 'should build a view header w/ style',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         style: STYLE,
  //         image: IMAGE,
  //         title: TITLE
  //       } as ViewHeaderDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'style', 'image', 'title'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.HEADER, 'Default', ViewMode.DISPLAY, STYLE, buildView(IMAGE), buildView(TITLE)]
  //     },
  //     {
  //       description: 'should build a view header w/ cssId',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         cssId: CSS_ID,
  //         image: IMAGE,
  //         title: TITLE
  //       } as ViewHeaderDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'cssId', 'image', 'title'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.HEADER, 'Default', ViewMode.DISPLAY, CSS_ID, buildView(IMAGE), buildView(TITLE)]
  //     },
  //     {
  //       description: 'should build a view header w/ class',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         class: CLASS,
  //         image: IMAGE,
  //         title: TITLE
  //       } as ViewHeaderDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'class', 'image', 'title'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.HEADER, 'Default', ViewMode.DISPLAY, CLASS + ' ' + View.VIEW_CLASS + ' ' + ViewHeader.HEADER_CLASS, buildView(IMAGE), buildView(TITLE)]
  //     },
  //     {
  //       description: 'should build a view header w/ visibility type',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         visibilityType: VISIBILITY_TYPE,
  //         image: IMAGE,
  //         title: TITLE
  //       } as ViewHeaderDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'visibilityType', 'image', 'title'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.HEADER, 'Default', ViewMode.DISPLAY, VISIBILITY_TYPE, buildView(IMAGE), buildView(TITLE)]
  //     },
  //     {
  //       description: 'should build a view header w/ events',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         events: EVENTS,
  //         image: IMAGE,
  //         title: TITLE
  //       } as ViewHeaderDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'events', 'image', 'title'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.HEADER, 'Default', ViewMode.DISPLAY, {'click': new EventGoToPage(EventType.CLICK, 10)}, buildView(IMAGE), buildView(TITLE)]
  //     },
  //   ];
  //
  //   parameters.forEach(parameter => {
  //     it(parameter.description, () => {
  //       const view = buildView(parameter.obj) as ViewHeader;
  //       expect(view).toBeTruthy();
  //       for (let i = 0; i < parameter.outputKeys.length; i++) {
  //         const key: string = parameter.outputKeys[i];
  //         const value: any = parameter.outputValues[i];
  //
  //         if (key === 'image') (value as ViewImage).class += ' ' + ViewHeader.IMAGE_CLASS;
  //         else if (key === 'title') (value as ViewText).class += ' ' + ViewHeader.TITLE_CLASS;
  //
  //         if (['image', 'title', 'events'].includes(key)) expect(view[key]).toEqual(value);
  //         else expect(view[key]).toBe(value);
  //       }
  //     });
  //   });
  // })
  //
  // describe('Build View Table', () => {
  //   const TYPE = ViewTable.TABLE_CLASS;
  //   const ROW_TYPE = ViewRow.ROW_CLASS;
  //   const CONTENT = {
  //     id: (parseInt(ID) + 2).toString(),
  //     viewId: (parseInt(ID) + 2).toString(),
  //     parentId: ID,
  //     partType: ViewText.TEXT_CLASS,
  //     role: ROLE,
  //     edit: false,
  //     value: TEXT
  //   } as ViewTextDatabase;
  //   const HEADER_ROW = {
  //     id: (parseInt(ID) + 1).toString(),
  //     viewId: (parseInt(ID) + 1).toString(),
  //     parentId: ID,
  //     partType: ROW_TYPE,
  //     role: ROLE,
  //     edit: false,
  //     values: [
  //       {value: CONTENT},
  //       {value: CONTENT},
  //       {value: CONTENT}
  //     ]
  //   } as ViewRowDatabase;
  //   const ROW = {
  //     id: (parseInt(ID) + 2).toString(),
  //     viewId: (parseInt(ID) + 2).toString(),
  //     parentId: ID,
  //     partType: ROW_TYPE,
  //     role: ROLE,
  //     edit: false,
  //     values: [
  //       {value: CONTENT},
  //       {value: CONTENT},
  //       {value: CONTENT}
  //     ]
  //   } as ViewRowDatabase;
  //
  //   const parameters = [
  //     {
  //       description: 'should build a view table w/ only mandatory params',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         headerRows: [HEADER_ROW],
  //         rows: [ROW]
  //       } as ViewTableDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'headerRows', 'rows', 'nrColumns'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TABLE, 'Default', ViewMode.DISPLAY, [buildView(HEADER_ROW)], [buildView(ROW)], HEADER_ROW.values.length]
  //     },
  //     {
  //       description: 'should build a view table in edit mode',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: true,
  //         headerRows: [HEADER_ROW],
  //         rows: [ROW]
  //       } as ViewTableDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'headerRows', 'rows', 'nrColumns'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TABLE, 'Default', ViewMode.EDIT, [buildView(HEADER_ROW)], [buildView(ROW)], HEADER_ROW.values.length]
  //     },
  //     {
  //       description: 'should build a view table w/ style',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         style: STYLE,
  //         headerRows: [HEADER_ROW],
  //         rows: [ROW]
  //       } as ViewTableDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'style', 'headerRows', 'rows', 'nrColumns'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TABLE, 'Default', ViewMode.DISPLAY, STYLE, [buildView(HEADER_ROW)], [buildView(ROW)], HEADER_ROW.values.length]
  //     },
  //     {
  //       description: 'should build a view table w/ cssId',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         cssId: CSS_ID,
  //         headerRows: [HEADER_ROW],
  //         rows: [ROW]
  //       } as ViewTableDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'cssId', 'headerRows', 'rows', 'nrColumns'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TABLE, 'Default', ViewMode.DISPLAY, CSS_ID, [buildView(HEADER_ROW)], [buildView(ROW)], HEADER_ROW.values.length]
  //     },
  //     {
  //       description: 'should build a view table w/ class',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         class: CLASS,
  //         headerRows: [HEADER_ROW],
  //         rows: [ROW]
  //       } as ViewTableDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'class', 'headerRows', 'rows', 'nrColumns'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TABLE, 'Default', ViewMode.DISPLAY, CLASS + ' ' + View.VIEW_CLASS + ' ' + ViewTable.TABLE_CLASS, [buildView(HEADER_ROW)], [buildView(ROW)], HEADER_ROW.values.length]
  //     },
  //     {
  //       description: 'should build a view table w/ visibility type',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         visibilityType: VISIBILITY_TYPE,
  //         headerRows: [HEADER_ROW],
  //         rows: [ROW]
  //       } as ViewTableDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'visibilityType', 'headerRows', 'rows', 'nrColumns'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TABLE, 'Default', ViewMode.DISPLAY, VISIBILITY_TYPE, [buildView(HEADER_ROW)], [buildView(ROW)], HEADER_ROW.values.length]
  //     },
  //     {
  //       description: 'should build a view table w/ events',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         events: EVENTS,
  //         headerRows: [HEADER_ROW],
  //         rows: [ROW]
  //       } as ViewTableDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'events', 'headerRows', 'rows', 'nrColumns'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.TABLE, 'Default', ViewMode.DISPLAY, {'click': new EventGoToPage(EventType.CLICK, 10)}, [buildView(HEADER_ROW)], [buildView(ROW)], HEADER_ROW.values.length]
  //     },
  //   ];
  //
  //   parameters.forEach(parameter => {
  //     it(parameter.description, () => {
  //       const view = buildView(parameter.obj) as ViewTable;
  //       expect(view).toBeTruthy();
  //       for (let i = 0; i < parameter.outputKeys.length; i++) {
  //         const key: string = parameter.outputKeys[i];
  //         const value: any = parameter.outputValues[i];
  //
  //         if (key === 'headerRows') value.forEach(row => (row as ViewRow).values.forEach(header => (header as View).class += ' ' + ViewTable.TABLE_HEADER_CLASS));
  //         else if (key === 'rows') value.forEach(row => (row as ViewRow).values.forEach(r => (r as View).class += ' ' + ViewTable.TABLE_BODY_CLASS));
  //
  //         if (['headerRows', 'rows', 'events'].includes(key)) expect(view[key]).toEqual(value);
  //         else expect(view[key]).toBe(value);
  //       }
  //     });
  //   });
  // })
  //
  // describe('Build View Block', () => {
  //   const TYPE = ViewBlock.BLOCK_CLASS;
  //   const IMAGE = {
  //     id: (parseInt(ID) + 1).toString(),
  //     viewId: (parseInt(ID) + 1).toString(),
  //     parentId: ID,
  //     partType: ViewImage.IMAGE_CLASS,
  //     role: ROLE,
  //     edit: false,
  //     value: IMG_SRC
  //   } as ViewImageDatabase;
  //
  //   const parameters = [
  //     {
  //       description: 'should build a view block w/ only mandatory params',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         children: [IMAGE]
  //       } as ViewBlockDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'children'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.BLOCK, 'Default', ViewMode.DISPLAY, [buildView(IMAGE)]]
  //     },
  //     {
  //       description: 'should build a view block in edit mode',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: true,
  //         children: [IMAGE]
  //       } as ViewBlockDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'children'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.BLOCK, 'Default', ViewMode.EDIT, [buildView(IMAGE)]]
  //     },
  //     {
  //       description: 'should build a view block w/ style',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         style: STYLE,
  //         children: [IMAGE]
  //       } as ViewBlockDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'style', 'children'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.BLOCK, 'Default', ViewMode.DISPLAY, STYLE, [buildView(IMAGE)]]
  //     },
  //     {
  //       description: 'should build a view block w/ cssId',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         cssId: CSS_ID,
  //         children: [IMAGE]
  //       } as ViewBlockDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'cssId', 'children'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.BLOCK, 'Default', ViewMode.DISPLAY, CSS_ID, [buildView(IMAGE)]]
  //     },
  //     {
  //       description: 'should build a view block w/ class',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         class: CLASS,
  //         children: [IMAGE]
  //       } as ViewBlockDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'class', 'children'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.BLOCK, 'Default', ViewMode.DISPLAY, CLASS + ' ' + View.VIEW_CLASS + ' ' + ViewBlock.BLOCK_CLASS, [buildView(IMAGE)]]
  //     },
  //     {
  //       description: 'should build a view block w/ visibility type',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         visibilityType: VISIBILITY_TYPE,
  //         children: [IMAGE]
  //       } as ViewBlockDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'visibilityType', 'children'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.BLOCK, 'Default', ViewMode.DISPLAY, VISIBILITY_TYPE, [buildView(IMAGE)]]
  //     },
  //     {
  //       description: 'should build a view block w/ events',
  //       obj: {
  //         id: ID,
  //         viewId: ID,
  //         parentId: null,
  //         partType: TYPE,
  //         role: ROLE,
  //         edit: false,
  //         events: EVENTS,
  //         children: [IMAGE]
  //       } as ViewBlockDatabase,
  //       outputKeys: ['id', 'viewId', 'parentId', 'type', 'role', 'mode', 'events', 'children'],
  //       outputValues: [parseInt(ID), parseInt(ID), null, ViewType.BLOCK, 'Default', ViewMode.DISPLAY, {'click': new EventGoToPage(EventType.CLICK, 10)}, [buildView(IMAGE)]]
  //     },
  //   ];
  //
  //   parameters.forEach(parameter => {
  //     it(parameter.description, () => {
  //       const view = buildView(parameter.obj) as ViewBlock;
  //       expect(view).toBeTruthy();
  //       for (let i = 0; i < parameter.outputKeys.length; i++) {
  //         const key: string = parameter.outputKeys[i];
  //         const value: any = parameter.outputValues[i];
  //
  //         if (['children', 'events'].includes(key)) expect(view[key]).toEqual(value);
  //         else expect(view[key]).toBe(value);
  //       }
  //     });
  //   });
  // })

})
