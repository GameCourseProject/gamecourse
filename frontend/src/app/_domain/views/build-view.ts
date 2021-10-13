import {View, ViewDatabase} from "./view";
import {ViewType} from "./view-type";
import {ViewText, ViewTextDatabase} from "./view-text";
import {ViewImage, ViewImageDatabase} from "./view-image";
import {ViewTable, ViewTableDatabase} from "./view-table";
import {ViewBlock, ViewBlockDatabase} from "./view-block";
import {ViewHeader, ViewHeaderDatabase} from "./view-header";
import {ViewRow, ViewRowDatabase} from "./view-row";

/**
 * Builds a view from an object according to whatever type the view is.
 *
 * This function needs to be outside view.ts so as not to create a
 * Circular Dependency between View and its children.
 * @link https://stackoverflow.com/questions/49727530/how-to-move-typescript-classes-with-circular-dependency-into-separate-files
 * @class View
 *
 * @param obj
 */
export function buildView(obj: ViewDatabase): View {
  const type = obj.partType;
  if (type === ViewType.TEXT) return ViewText.fromDatabase(obj as ViewTextDatabase);
  else if (type === ViewType.IMAGE) return ViewImage.fromDatabase(obj as ViewImageDatabase);
  else if (type === ViewType.TABLE) return ViewTable.fromDatabase(obj as ViewTableDatabase);
  else if (type === ViewType.HEADER) return ViewHeader.fromDatabase(obj as ViewHeaderDatabase);
  else if (type === ViewType.ROW) return ViewRow.fromDatabase(obj as ViewRowDatabase);
  else if (type === ViewType.BLOCK) return ViewBlock.fromDatabase(obj as ViewBlockDatabase);
  return null;
}
