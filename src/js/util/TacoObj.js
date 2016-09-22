class TacoObj {
  isIterable(obj) {
    if(this.getObjectLength(obj) > -1) return true;
    return false;
  };

  objectJoin(joiner, object) {
    var s = '';
    for(var o in object) {
      s += (o + ' ' + object[o]);
    }
    return s;
  };

  getObjectLength(object) {
    if(typeof object != 'object') return -1;
    var count = 0;
    for(var o in object) {
      count++;
    }
    return count;
  };
}
export default new TacoObj();
