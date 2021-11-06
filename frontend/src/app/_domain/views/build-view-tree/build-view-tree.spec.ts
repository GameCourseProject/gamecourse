import {buildViewTree} from "./build-view-tree";
import {copyObject} from "../../../_utils/misc/misc";
import {View, ViewMode} from "../view";
import {ViewText} from "../view-text";
import {ViewImage} from "../view-image";
import {ViewHeader} from "../view-header";
import {ViewBlock} from "../view-block";

// TODO: create tests
describe('Build View Tree', () => {

  const TEXT = new ViewText(null, null, null, null, ViewMode.EDIT, 'This is some text.');
  const IMAGE = new ViewImage(null, null, null, null, ViewMode.EDIT, 'src/img.png');
  const HEADER = new ViewHeader(null, null, null, null, ViewMode.EDIT, null, null);
  const BLOCK = new ViewBlock(null, null, null, null, ViewMode.EDIT, null);

  it('should build empty view tree w/ empty view', () => {
    const aspects = [];
    const output = [];

    checkIfArraysAreEqual(buildViewTree(aspects), output);
  });

  describe('View Text', () => {
    it('should build view tree w/ one view aspect', () => {
      const text1 = getView(TEXT, 1, 1, null, 'role.Default');

      const aspects = [text1];
      const output = [text1];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });

    it('should build view tree w/ multiple view aspects', () => {
      const text1 = getView(TEXT, 1, 1, null, 'role.Default');
      const text2 = getView(TEXT, 2, 1, null, 'role.Student');
      const text3 = getView(TEXT, 3, 1, null, 'role.StudentA');

      const aspects = [text1, text2, text3];
      const output = [text1, text2, text3];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });
  });

  describe('View Image', () => {
    it('should build view tree w/ one view aspect', () => {
      const image1 = getView(IMAGE, 1, 1, null, 'role.Default');

      const aspects = [image1];
      const output = [image1];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });

    it('should build view tree w/ multiple view aspects', () => {
      const image1 = getView(IMAGE, 1, 1, null, 'role.Default');
      const image2 = getView(IMAGE, 2, 1, null, 'role.Student');
      const image3 = getView(IMAGE, 3, 1, null, 'role.StudentA');

      const aspects = [image1, image2, image3];
      const output = [image1, image2, image3];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });
  });

  describe('View Header', () => {
    it('should build view tree w/ one view aspect', () => {
      const header1 = getView(HEADER, 1, 1, null, 'role.Default') as ViewHeader;
      header1.image = getView(IMAGE, 2, 2, 1, 'role.Default') as ViewImage;
      header1.title = getView(TEXT, 3, 3, 1, 'role.Default') as ViewText;

      const aspects = [header1];

      const headerOutput = copyObject(header1);
      headerOutput.image = [copyObject(header1.image)];
      headerOutput.title = [copyObject(header1.title)];
      const output = [headerOutput];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });

    it('should build view tree w/ multiple view aspects', () => {
      const header1 = getView(HEADER, 1, 1, null, 'role.Default') as ViewHeader;
      header1.image = getView(IMAGE, 2, 2, 1, 'role.Default') as ViewImage;
      header1.title = getView(TEXT, 3, 3, 1, 'role.Default') as ViewText;

      const header2 = getView(HEADER, 1, 1, null, 'role.Default') as ViewHeader;
      header2.image = getView(IMAGE, 5, 2, 1, 'role.Student') as ViewImage;
      header2.title = getView(TEXT, 6, 3, 1, 'role.Student') as ViewText;

      const aspects = [header1, header2];

      const headerOutput1 = copyObject(header1);
      headerOutput1.image = [copyObject(header1.image), copyObject(header2.image)];
      headerOutput1.title = [copyObject(header1.title), copyObject(header2.title)];
      const output = [headerOutput1];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });

    it('should build view tree w/ multiple view aspects', () => {
      const header1 = getView(HEADER, 1, 1, null, 'role.Default') as ViewHeader;
      header1.image = getView(IMAGE, 2, 2, 1, 'role.Default') as ViewImage;
      header1.title = getView(TEXT, 3, 3, 1, 'role.Default') as ViewText;

      const header2 = getView(HEADER, 4, 4, null, 'role.Student') as ViewHeader;
      header2.image = getView(IMAGE, 5, 5, 4, 'role.Student') as ViewImage;
      header2.title = getView(TEXT, 6, 6, 4, 'role.Student') as ViewText;

      const aspects = [header1, header2];

      const headerOutput1 = copyObject(header1);
      headerOutput1.image = [copyObject(header1.image)];
      headerOutput1.title = [copyObject(header1.title)];
      const headerOutput2 = copyObject(header2);
      headerOutput2.image = [copyObject(header2.image)];
      headerOutput2.title = [copyObject(header2.title)];
      const output = [headerOutput1, headerOutput2];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });
  });

  describe('View Block', () => {
    it('should build view tree w/ one view aspect', () => {
      const block1 = getView(BLOCK, 1, 1, null, 'role.Default') as ViewBlock;
      block1.children = [
        getView(TEXT, 2, 2, 1, 'role.Default'),
        getView(TEXT, 3, 3, 1, 'role.Default'),
      ];

      const aspects = [block1];

      const blockOutput = copyObject(block1);
      blockOutput.children = [
        [copyObject(block1.children[0])],
        [copyObject(block1.children[1])],
      ];
      const output = [blockOutput];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });

    it('should build view tree w/ multiple view aspects', () => {
      const block1 = getView(BLOCK, 1, 1, null, 'role.Default') as ViewBlock;
      block1.children = [
        getView(TEXT, 2, 2, 1, 'role.Default'),
        getView(TEXT, 3, 3, 1, 'role.Default'),
      ];
      const block2 = getView(BLOCK, 1, 1, null, 'role.Default') as ViewBlock;
      block2.children = [
        getView(TEXT, 4, 2, 1, 'role.Student'),
        getView(TEXT, 5, 3, 1, 'role.Student'),
      ];

      const aspects = [block1, block2];

      const blockOutput = copyObject(block1);
      blockOutput.children = [
        [copyObject(block1.children[0]), copyObject(block2.children[0])],
        [copyObject(block1.children[1]), copyObject(block2.children[1])],
      ];
      const output = [blockOutput];

      checkIfArraysAreEqual(buildViewTree(aspects), output);
    });

    // TODO: more
  });

  describe('View Row', () => {
    // TODO
  });

  describe('View Table', () => {
    // TODO
  });


  function getView(view: View, id: number, viewId: number, parentId: number, role: string): View {
    const copy = copyObject(view);
    copy.id = id;
    copy.viewId = viewId;
    copy.parentId = parentId;
    copy.role = role;
    return copy;
  }

  function checkIfArraysAreEqual(arr1: any[], arr2: any[]): void {
    expect(arr1.length).toBe(arr2.length);

    if (arr1.length > 0) {
      for (let i = 0; i < arr1.length; i++) {
        if (Array.isArray(arr1[i])) {  // Child is an array
          checkIfArraysAreEqual(arr1[i], arr2[i]);

        } else {  // Child is an object
          checkIfObjectsAreEqual(arr1[i], arr2[i]);
        }
      }
    }
  }

  function checkIfObjectsAreEqual(obj1, obj2) {
    const keys1 = Object.keys(obj1);
    const keys2 = Object.keys(obj2);
    expect(keys1.length).toBe(keys2.length);

    // Check all keys
    for (const key of keys1) {
      expect(obj2.hasOwnProperty(key)).toBeTrue();

      if (typeof obj1[key] !== 'object' || obj1[key] === null) {
        expect(obj1[key]).toBe(obj2[key]);

      } else if (Array.isArray(obj1[key])) {
        checkIfArraysAreEqual(obj1[key], obj2[key]);

      } else {
        checkIfObjectsAreEqual(obj1[key], obj2[key]);
      }
    }
  }

});
