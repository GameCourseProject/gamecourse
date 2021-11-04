import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";
import {copyObject} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";

export class ViewRow extends View {

  private _children: View[];

  static readonly ROW_CLASS = 'row';
  static readonly ROW_CHILDREN_CLASS = 'row_children';
  static readonly ROW_EMPTY_CLASS = 'row_empty';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, children: View[], loopData?: any,
              variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any) {

    super(id, viewId, parentId, ViewType.ROW, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.children = children;
  }

  get children(): View[] {
    return this._children;
  }

  set children(value: View[]) {
    this._children = value;
  }

  updateView(newView: View): ViewRow {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      ViewSelectionService.unselect(copy);
      return copy as ViewRow;
    }

    // Check if child
    for (let i = 0; i < this.children.length; i++) {
      const child = this.children[i];
      const newChild = child.updateView(newView);
      if (newChild !== null) {
        this.children[i] = newChild;
        return this;
      }
    }

    return null;
  }

  static fromDatabase(obj: ViewRowDatabase): ViewRow {
    const parsedObj = View.parse(obj);
    return new ViewRow(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      obj.children.map(child => buildView(child)),
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.ROW_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewRowDatabase extends ViewDatabase {
  children: ViewDatabase[];
}
