import TacoObj from './TacoObj.js';

class TacoArr {
  inArray(value, array) {
    if(TacoObj.getObjectLength(array) < 1) return false;
    for(var a in array) {
      if(array[a] == value) return true;
    }
    return false;
  };
}
export default new TacoArr;
