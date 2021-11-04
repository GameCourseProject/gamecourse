import {View, ViewDatabase, ViewMode, VisibilityType} from "./view";
import {ViewType} from "./view-type";
import {buildView} from "./build-view";
import {copyObject} from "../../_utils/misc/misc";
import {ViewSelectionService} from "../../_services/view-selection.service";

export class ViewBlock extends View {

  private _children: View[];

  // Edit only params
  private _isEditingLayout?: boolean;

  static readonly BLOCK_CLASS = 'block';
  static readonly BLOCK_CHILDREN_CLASS = 'block_children';
  static readonly BLOCK_EMPTY_CLASS = 'block_empty';

  constructor(id: number, viewId: number, parentId: number, role: string, mode: ViewMode, children: View[], loopData?: any,
              variables?: any, style?: string, cssId?: string, cl?: string, label?: string, visibilityType?: VisibilityType,
              visibilityCondition?: any, events?: any) {

    super(id, viewId, parentId, ViewType.BLOCK, role, mode, loopData, variables, style, cssId, cl, label, visibilityType,
      visibilityCondition, events);

    this.children = children;
  }

  get children(): View[] {
    return this._children;
  }

  set children(value: View[]) {
    this._children = value;
  }

  get isEditingLayout(): boolean {
    return this._isEditingLayout;
  }

  set isEditingLayout(value: boolean) {
    this._isEditingLayout = value;
  }

  updateView(newView: View): ViewBlock {
    if (this.id === newView.id) {
      const copy = copyObject(newView);
      ViewSelectionService.unselect(copy);
      return copy as ViewBlock;
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

  static fromDatabase(obj: ViewBlockDatabase): ViewBlock {
    const parsedObj = View.parse(obj);
    return new ViewBlock(
      parsedObj.id,
      parsedObj.viewId,
      parsedObj.parentId,
      parsedObj.role,
      parsedObj.mode,
      obj.children?.length > 0 ? obj.children.map(child => buildView(child)) : [],
      parsedObj.loopData,
      parsedObj.variables,
      parsedObj.style,
      parsedObj.cssId,
      parsedObj.class + ' ' + this.BLOCK_CLASS,
      parsedObj.label,
      parsedObj.visibilityType,
      parsedObj.visibilityCondition,
      parsedObj.events
    );
  }
}

export interface ViewBlockDatabase extends ViewDatabase {
  children?: ViewDatabase[];
}
